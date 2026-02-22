<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DataSource;
use App\Models\Service;
use App\Models\Report;
use App\Models\User;
use App\Models\EmailServer;
use App\Models\FtpServer;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@system.local')->first();

        // 1. Data Sources
        $mysqlDs = DataSource::updateOrCreate(
            ['name' => 'Internal Operational DB'],
            [
                'type' => 'mysql',
                'connection_config' => [
                    'host' => 'db',
                    'port' => 3306,
                    'database' => 'rbdb',
                    'username' => 'rbdb',
                    'password' => 'root', 
                    'options' => ['charset' => 'utf8mb4'],
                ],
            ]
        );

        $oracleDs = DataSource::updateOrCreate(
            ['name' => 'Legacy Core Banking'],
            [
                'type' => 'oracle',
                'connection_config' => [
                    'host' => 'oracle',
                    'port' => 1521,
                    'database' => 'FREEPDB1',
                    'username' => 'post_admin',
                    'password' => 'password',
                ],
            ]
        );

        // 2. Services
        $logistics = Service::updateOrCreate(
            ['name' => 'Logistics & Distribution'],
            ['description' => 'Fleet management, package tracking, and distribution optimization.']
        );

        $finance = Service::updateOrCreate(
            ['name' => 'Financial Services'],
            ['description' => 'Revenue reconciliation, commission calculation, and audit logs.']
        );

        // 3. Reports
        Report::updateOrCreate(
            ['name' => 'Daily Revenue Summary'],
            [
                'service_id' => $finance->id,
                'data_source_id' => $mysqlDs->id,
                'type' => 'sql',
                'sql_definition' => 'SELECT status, count(*) as total FROM executions GROUP BY status',
                'created_by' => $admin->id,
            ]
        );

        Report::updateOrCreate(
            ['name' => 'Package Delivery Efficiency'],
            [
                'service_id' => $logistics->id,
                'data_source_id' => $oracleDs->id,
                'type' => 'sql',
                'sql_definition' => 'SELECT sysdate as "Report Date", 100 as "Accuracy %" FROM dual',
                'created_by' => $admin->id,
            ]
        );
        
        Report::updateOrCreate(
            ['name' => 'User Activity Audit'],
            [
                'service_id' => $finance->id,
                'data_source_id' => $mysqlDs->id,
                'type' => 'sql',
                'sql_definition' => 'SELECT name, email, last_login_at FROM users WHERE status = "active"',
                'created_by' => $admin->id,
            ]
        );

        // 4. Infrastructure (Docker Testing)
        EmailServer::updateOrCreate(
            ['name' => 'MailHog Development'],
            [
                'host' => 'mailhog',
                'port' => 1025,
                'encryption' => 'none',
                'from_address' => 'system@rbdb.local',
                'from_name' => 'RBDB Control Plane',
                'is_active' => true,
            ]
        );

        FtpServer::updateOrCreate(
            ['name' => 'Internal FTP Node'],
            [
                'host' => 'ftp',
                'port' => 21,
                'username' => 'ftpuser',
                'password' => 'ftppass',
                'root_path' => '/',
                'passive_mode' => true,
                'is_active' => true,
            ]
        );
    }
}
