<?php

namespace App\Services;
use Log;

class FtpService
{
    public static function download($ftp_file_name, $file_name, $file_path = null)
    {
        try {
            $file_path = $file_path ? $file_path : storage_path();
            $host = env('FTP_HOST');
            $username = env('FTP_USERNAME');
            $password = env('FTP_PASSWORD');
            $connection = ftp_connect($host);
            $login = ftp_login($connection, $username, $password);
            ftp_pasv($connection, true);
            if (ftp_get($connection, $file_path . '/' . $file_name, $ftp_file_name, FTP_BINARY)) {
                Log::error("Succesfully fetched file from " . $host);
            } else {
                Log::error("Couldn't fetch file from " . $host);
                return null;
            }
            ftp_close($connection);
            return $file_path . '/' . $file_name;
        } catch (\Exception $e) {
            Log::error('Errour occured during fetching data from ' . $host);
            return null;
        }
    }
}
