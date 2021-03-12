<template>
  <v-app>
    <div data-app class="container">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">TRUI</div>
            <div class="card-body">
              <div v-for="(filter, key) in filtered" :key="key">
                <v-select
                  ref="filterSelect"
                  return-object
                  v-if="filter.active && !filter.options_file_names"
                  :items="filter.options"
                  item-text="name"
                  no-data-text=""
                  :label="filter.name"
                  :value="filter"
                  v-on:input="(selected) => updateSelected(selected, filter)"
                ></v-select>
              </div>
            </div>
            <div class="text-center">
              <h4 v-if="files.length > 0">Files</h4>
              <v-chip
                v-for="(file, key) in files"
                :key="key"
                class="ma-2"
                color="primary"
                label
                outlined
                >{{file}}
              </v-chip>
            </div>
            <v-btn
              class="ma-2"
              :disabled="files.length == 0"
              color="success"
              :href="downloadUrl"
              >DOWNLOAD</v-btn
            >
          </div>
          <v-snackbar v-model="snackbar" :multi-line="true" color="red">
            {{ snackbarText }}
          </v-snackbar>
        </div>
      </div>
    </div>
  </v-app>
</template>

<script>
export default {
    data () {
        return {
            initialFilters: [],
            filtered: [],
            step: 0,
            files: [],
            path: [],
            snackbar: false,
            snackbarText: false,
        }
    },
    mounted() {
        axios.get('/api/test/filters')
            .then(response => {
                if (response.code == 503) {
                    console.log(response.status)
                    return false
                }
                $.each(response.data.filters, (key, value) => {
                    value.step = key
                    value.active = false
                })
                this.initialFilters = response.data.filters
                this.initialFilters[0].active = true
                this.filtered.push(...JSON.parse(JSON.stringify(this.initialFilters)))
            }).catch (err => {
                if (err.response.status == 503) {
                    this.snackbar = true
                    this.snackbarText = err.response.data.status
                    return false
                }
            })
    },
    computed: {
        downloadUrl: function () {
            let url = "/api/test/files?"
            $.each(this.files, (key, value) => {
                url += 'files[]=' + value + '&'
            })
            return url
        }
    },
    methods: {
        updateSelected(selected, filter) {
            this.files = []
            this.resetRemainingOptions(filter)
            if (selected) {
                let hasNullOptionsOnly = false
                let nextFilter
                do {
                    let range = this.getRange(selected, filter)
                    this.path[filter.step] = range
                    nextFilter = this.filtered[filter.step + 1]
                        nextFilter.options = nextFilter.options.filter(option => {
                            let inRange = false;
                            $.each(this.path, (key, range) => {
                                let optionRange = this.getRange(option, nextFilter)
                                if (range.to > optionRange.from && optionRange.to > range.from) {
                                    inRange = true
                                }
                                else {
                                    inRange = false
                                    return false
                                }
                            })
                            return inRange
                        })
                    hasNullOptionsOnly = this.filterHasOnlyNullOptions(nextFilter)
                    if (hasNullOptionsOnly) {
                        filter = nextFilter
                        selected = nextFilter.options[0]
                    }
                } while(hasNullOptionsOnly)
                nextFilter.active = true
                if (nextFilter.options_file_names) {
                    if (Array.isArray(nextFilter.options[0].name)) this.files = nextFilter.options[0].name
                    else this.files.push(nextFilter.options[0].name)
                }
            }
        },
        filterHasOnlyNullOptions(nextFilter) {
            return nextFilter.options.length == 1 && nextFilter.options[0].name == null
        },
        getRange(selected, filter) {
            let filteredArray =  filter.options.filter(option => {
                return option.key > selected.key
            })
            return {
                'from': selected.key,
                'to': filteredArray.length == 0 ? this.initialFilters.length + 1 : filteredArray[0].key
            }
        },
        resetRemainingOptions(selectedFilter) {
            let reimaingFiltersInitial = this.initialFilters.slice(selectedFilter.step + 1, this.initialFilters.length).map((obj) => {
                    return obj
                })
            this.filtered.splice(-reimaingFiltersInitial.length, reimaingFiltersInitial.length)
            this.path.splice(selectedFilter.step , this.path.length - selectedFilter.step )
            this.filtered.push(...JSON.parse(JSON.stringify(reimaingFiltersInitial)))
        }
    }
}
</script>
