<?php

namespace App\Classes;

use App\Interfaces\AbstractImport;
use App\Services\SqlServerService;

class ImportPpsm extends AbstractImport
{
    protected $connection;
    protected $subjects;
    protected $datasourceKey;

    public function __construct()
    {
        $serverName = $_ENV['SQL_SERVER_HOST'];
        $username = $_ENV['SQL_SERVER_USER'];
        $password = $_ENV['SQL_SERVER_PASSWORD'];

        $this->connection = SqlServerService::getConnection($serverName, $username, $password, 'SPACEDB1000');

        // Set datasource key
        $this->datasourceKey = '212201_OCT_PS_PPSM';

        // Add subjects to import here
        $this->subjects = "
            'SKAB3842',
            'SKAA3842',
            'SAB3842',
            'SKAB3712',
            'SKAA3712',
            'SAB3712',
            'SKAB3613',
            'SKAA3613',
            'SAB3613',
            'SKAB3412',
            'SKAB3913',
            'SKAA3913',
            'SAB4913',
            'SKAB3323',
            'SKAA3233',
            'SKAB4223',
            'SKAA4223',
            'SKAA4022',
            'SAB4022',
            'SKAA4042',
            'SAB4012',
            'SKAA4943',
            'SKAA4753',
            'SKAA4723',
            'SKAA4143',
            'SKAA4613',
            'SKAA4034',
            'SAB4034',
            'SKAA4412',
            'SAB4412',
            'SKAA4983',
            'SKAA4163',
            'SKAB1513',
            'SKAA1513',
            'SKAB1213',
            'SKAA1213',
            'SKAB1713',
            'SKAA1713',
            'SKAA4333',
            'SAB4333',
            'SKAA3045',
            'SAB3045',
            'SSCE1693',
            'SSE1792',
            'SEAA2912',
            'SKAB2912',
            'SKAA2912',
            'SEAA2112',
            'SKAB2112',
            'SKAA2112',
            'SEAA2223',
            'SKAB2223',
            'SKAA2223',
            'SAB2223',
            'SSCE1793',
            'SSE1793',
            'SEAA2513',
            'SKAB2513',
            'SKAA2513',
            'SEAA2712',
            'SKAB2712',
            'SKAA2712',
            'SEAA2922',
            'SKAB2922',
            'SKAA2922',
            'SSCE1993',
            'SSE1893',
            'SEAA2413',
            'SKAB2413',
            'SKAA3413',
            'SEAA2722',
            'SKAB2722',
            'SKAA2722',
            'SSCE2193',
            'SSE2193',
            'SKAB2032',
            'SKAA2032',
            'SAB2032',
            'SKAB2832',
            'SKAA2832',
            'SAB2832',
            'SKAB3243',
            'SKAA3243',
            'SAB3243',
            'SKAB3313',
            'SKAA3352',
            'SAB3353',
            'SKAB3123',
            'SKAA3122',
            'SAB3122',
            'SSCE2393',
            'SSE2393',
            'SHAD2113',
            'SHAR2073',
            'SHMR2073',
            'SHAR2043',
            'SHAR3163',
            'SHP1323',
            'SHAR3093',
            'SHP2423',
            'SHAY1073',
            'SHAR3173',
            'SHMR2083',
            'SHAR2083',
            'SHAR2063',
            'SHAR3133',
            'SHAR3123',
            'SHMR1023',
            'SHP3313',
            'SHAR1023',
            'SHAR3113',
            'SHAR2113',
            'SHP3393',
            'SHMR1033',
            'SHAR1033',
            'SHP1343',
            'SHAY2083',
            'SHMY2083',
            'SHMY1033',
            'SHAY1033',
            'SHMR2033',
            'SHAR2033',
            'SHP3383',
            'SHAY3023',
            'SHP2363',
            'SHAR1053',
            'SHMR1053',
            'SHMY3043',
            'SHAY3043',
            'SHP2393',
            'SHMR4013',
            'SHAR4013',
            'SHAR3063',
            'SHAR1063',
            'SHMR1063',
            'SHAD1033',
            'SHMR1083',
            'SHAR1083',
            'SHAD2023',
            'SHAR1073',
            'SHMR1073',
            'SHAD1043',
            'SHD1523',
            'SHAR4043',
            'SHAR4053',
            'SHAR3013',
            'SHP2333',
            'SHAR3023',
            'SHP3423',
            'SHAR2053',
            'SHP1353',
            'SHAD2013',
            'SHMY2103',
            'SHAY2103',
            'SHMY1053',
            'SHAY1053'        
        ";
    }

    public function processLecturers()
    {
        $sql = "
        SELECT DISTINCT a.lecID AS external_person_key,
        '{$this->datasourceKey}' AS data_source_key,
        UPPER(a.lecName) AS firstname,
        '' AS lastname,
        UPPER(a.lecID) AS [user_id],
        a.lecICNo AS passwd,
        'Y' AS available_ind,
        email,
        'staff' AS institution_role
        FROM Lecturer a
        JOIN SubjOffered b ON b.lecID = a.lecID
        JOIN SesSem c ON c.sesSemNo = b.sesSemNo AND c.[status] = 'C'
        WHERE b.subjCode IN ({$this->subjects})";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processStudents()
    {
        $sql = "
        SELECT DISTINCT UPPER(b.stuMetricNo) AS external_person_key,
        '{$this->datasourceKey}' AS data_source_key,
        UPPER(b.stuName) AS firstname,
        '' AS lastname,
        UPPER(b.stuMetricNo) AS [user_id],
        b.stuICNo AS [passwd],
        'Y' AS available_ind,
        b.eMail AS email,
        'student' AS institution_role 
        FROM StuRegSubj a
        JOIN Main b ON b.stuRef = a.stuRef
        JOIN SesSem c ON c.sesSemNo = a.sesSemNo AND c.[status] = 'C'
        WHERE a.subjCode IN ({$this->subjects})";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processCourses()
    {
        $sql = "
        SELECT DISTINCT 
        a.subjCode + '_' + a.section + '_' + SUBSTRING(d.sesName, 3, 2) +  SUBSTRING(d.sesName, 8, 2) + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        a.subjCode + '_' + a.section + '_' + SUBSTRING(d.sesName, 3, 2) +  SUBSTRING(d.sesName, 8, 2) + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS course_id,
        'SEM ' + SUBSTRING(d.sesName, 3, 2) + SUBSTRING(d.sesName, 8, 2) + '-' + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + ': ' + UPPER(b.subjNameBI) AS course_name,
        '{$this->datasourceKey}' AS data_source_key
        FROM StuRegSubj a
        INNER JOIN Subj b ON b.subjCode = a.subjCode 
        INNER JOIN Fac c ON c.facCode = b.facCode
        INNER JOIN SesSem d ON d.sesSemNo = a.sesSemNo AND d.[status] = 'C'
        WHERE a.subjCode IN ({$this->subjects})";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'course');
    }

    public function processCourseLecturers()
    {
        $sql = "
        SELECT a.subjCode + '_' + a.section + '_' + SUBSTRING(b.sesName, 3, 2) +  SUBSTRING(b.sesName, 8, 2) + RIGHT('00' + CAST(b.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        a.lecID AS external_person_key,
        'instructor' AS [role],
        '{$this->datasourceKey}' AS data_source_key
        FROM SubjOffered a
        JOIN SesSem b ON b.sesSemNo = a.sesSemNo AND b.[status] = 'C'
        WHERE RTRIM(LTRIM(a.lecID)) IS NOT NULL
        AND a.subjCode IN ({$this->subjects})";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }

    public function processEnrollments()
    {
        $sql = "
        SELECT b.stuMetricNo AS external_person_key,
        a.subjCode + '_' + a.section + '_' + SUBSTRING(c.sesName, 3, 2) +  SUBSTRING(c.sesName, 8, 2) + RIGHT('00' + CAST(c.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        'student' AS [role],
        '{$this->datasourceKey}' AS data_source_key
        FROM StuRegSubj a
        JOIN Main b ON b.stuRef = a.stuRef
        JOIN SesSem c ON c.sesSemNo = a.sesSemNo AND c.[status] = 'C'
        WHERE a.subjCode IN ({$this->subjects})";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }
}