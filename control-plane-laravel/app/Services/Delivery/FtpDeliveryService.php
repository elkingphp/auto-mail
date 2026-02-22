<?php

namespace App\Services\Delivery;

use App\Models\FtpServer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FtpDeliveryService
{
    /**
     * Upload a file to an FTP server.
     *
     * @param FtpServer $server
     * @param string $localFilePath
     * @param string $remoteFilename
     * @return bool
     */
    public function upload(FtpServer $server, string $localFilePath, string $remoteFilename): bool
    {
        try {
            // ... (existing code via verifyConnection?)
            // Refactor to reuse logic if possible, or just duplicate config build
            $disk = $this->buildDisk($server);
            
            // ...
            $fileHandle = fopen($localFilePath, 'r');
            $success = $disk->put($remoteFilename, $fileHandle);
            if (is_resource($fileHandle)) fclose($fileHandle);
            
            if ($success) {
                Log::info("FTP Upload Success: {$server->name} -> {$remoteFilename}");
            } else {
                Log::error("FTP Upload Failed: {$server->name} -> {$remoteFilename}");
            }

            return $success;

        } catch (\Exception $e) {
            Log::error("FTP Delivery Exception: {$e->getMessage()}", [
                'server_id' => $server->id,
                'host' => $server->host
            ]);
            return false;
        }
    }

    public function listFiles(FtpServer $server, string $path = '/'): array
    {
        $disk = $this->buildDisk($server);
        
        // List with metadata (recursive=false)
        $listing = $disk->listContents($path, false);
        $results = [];
        
        foreach ($listing as $item) {
            $path = $item->path();
            $meta = [
                'name' => basename($path),
                'path' => $path,
                'type' => $item->type(), // 'file' or 'dir'
                'size' => null,
                'last_modified' => null,
            ];

            if ($item->type() === 'file') {
                try {
                    $meta['size'] = $item->fileSize();
                } catch (\Throwable $e) {
                     // Metadata might not be available
                }
                
                try {
                    $meta['last_modified'] = $item->lastModified();
                } catch (\Throwable $e) {}
            }

            $results[] = $meta;
        }
        
        return $results;
    }

    public function makeDirectory(FtpServer $server, string $path): bool
    {
        return $this->buildDisk($server)->makeDirectory($path);
    }

    public function delete(FtpServer $server, string $path): bool
    {
        return $this->buildDisk($server)->delete($path);
    }

    public function deleteDirectory(FtpServer $server, string $path): bool
    {
        return $this->buildDisk($server)->deleteDirectory($path);
    }

    public function uploadFile(FtpServer $server, string $path, $contents): bool
    {
        return $this->buildDisk($server)->put($path, $contents);
    }

    public function getFile(FtpServer $server, string $path): ?string
    {
        return $this->buildDisk($server)->get($path);
    }

    public function verifyConnection(FtpServer $server): bool
    {
        try {
            $disk = $this->buildDisk($server);
            $disk->listContents('/', false); 
            
            // If we reach here, update server status
            $server->update([
                'status' => 'online',
                'last_check_at' => now()
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("FTP Verification Failed: {$e->getMessage()}");
            
            $server->update([
                'status' => 'offline',
                'last_check_at' => now()
            ]);
            
            return false;
        }
    }

    public function buildDisk(FtpServer $server): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $config = [
            'driver'   => 'ftp',
            'host'     => $server->host,
            'username' => $server->username,
            'password' => $server->password,
            'port'     => $server->port,
            'root'     => $server->root_path ?? '/',
            'passive'  => $server->passive_mode,
            'ssl'      => false,
            'timeout'  => 10,
        ];

        try {
            return Storage::build($config);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'FtpAdapter')) {
                throw new \RuntimeException('FTP functionality requires league/flysystem-ftp package.');
            }
            throw $e;
        }
    }
}
