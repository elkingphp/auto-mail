$ftpServer = \App\Models\FtpServer::create([
    'name' => 'Docker Test FTP',
    'host' => 'ftp',
    'port' => 21,
    'username' => 'ftpuser',
    'password' => 'ftppass',
    'root_path' => '/',
    'passive_mode' => true,
    'is_active' => true
]);

echo "FTP Server Created: {$ftpServer->id}\n";
echo "Name: {$ftpServer->name}\n";
echo "Host: {$ftpServer->host}\n";
