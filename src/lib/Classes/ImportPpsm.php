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

        // It cannot use a different datasource key.
        // Unless you want to update the .env file every semester.
        $this->datasourceKey = 'TryIntg01';

        // Add subjects to import here
        $this->subjects = "
            'SEAA2912',
            'SKAB2912',
            'SKAA2912',
            'SEAA2112',
            'SKAB2112',
            'SKAA2112',
            'SEAA2223',
            'SKAB2223',
            'SKAA2223',
            'SEAA2912',
            'SKAB2912',
            'SKAA2912',
            'SEAA2112',
            'SKAB2112',
            'SKAA2112',
            'SEAA2223',
            'SKAB2223',
            'SKAA2223',
            'SEAA2712',
            'SKAB2712',
            'SKAA2712',
            'SEAA2922',
            'SKAB2922',
            'SKAA2922',
            'SEAA2513',
            'SKAB2513',
            'SKAA2513',
            'SEAA2712',
            'SKAB2712',
            'SEAA2922',
            'SKAB2922',
            'SKAA2922',
            'SEAA2513',
            'SKAB2513',
            'SKAA2513',
            'SEAA2712',
            'SKAB2712',
            'SKAA2712',
            'SEAA2922',
            'SKAB2922',
            'SKAA2922',
            'SEAA2513',
            'SKAB2513',
            'SKAA2513',
            'SEAA2722',
            'SKAB2722',
            'SKAA2722',
            'SEAA2413',
            'SKAB2413',
            'SKAA3413',
            'SEAA2413',
            'SKAB2413',
            'SKAA3413',
            'SEAA2722',
            'SKAB2722',
            'SKAA2722',
            'SEAA2413',
            'SKAB2413',
            'SKAA3413',
            'SEAA2722',
            'SKAB2722',
            'SKAA2722',
            'SEAA2413',
            'SKAB2413',
            'SKAA3413',
            'SEAA2413',
            'SKAB2413',
            'SKAA3413',
            'SEAA2832',
            'SKAB2832',
            'SKAA2832',
            'SEAA3243',
            'SKAB3243',
            'SKAA3243',
            'SEAA2832',
            'SKAB2832',
            'SKAA2832',
            'SEAA3243',
            'SKAB3243',
            'SKAA3243',
            'SKAB3313',
            'SKAA3352',
            'SAB3353',
            'SKAB3123',
            'SKAA3122',
            'SKAB3313',
            'SKAA3352',
            'SAB3353',
            'SKAB3123',
            'SKAA3122',
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
            'SKA3913',
            'SKAA3913',
            'SAB4913',
            'SKAB3323',
            'SKAA3233',
            'SKAB4223',
            'SKAA4223',
            'SKAB4333',
            'SKAA4333',
            'SAB4333',
            'SKAB4113',
            'SKAA4113',
            'SKAB3022',
            'SKAA3021',
            'SKAA3031',
            'SKAB4333',
            'SKAA4333',
            'SAB4333',
            'SKAB4113',
            'SKAA4113',
            'SKAB3022',
            'SKAA3021',
            'SKAA3031',
            'SKAB4333',
            'SKAA4333',
            'SAB4333',
            'SKAB4113',
            'SKAA4113',
            'SKAB3022',
            'SKAA3021',
            'SKAA3031',
            'SKAA4412',
            'SAB4412',
            'SKAA4613',
            'SKAA4034',
            'SAB4034',
            'SKAA4412',
            'SAB4412',
            'SKAA4163',
            'SKAA4034',
            'SAB4034',
            'SKAA4412',
            'SAB4412',
            'SKAA4983',
            'SKAA4034',
            'SAB4034',
            'SKAB1513',
            'SKAA1513',
            'SKAA4022',
            'SAB4022',
            'SKAB1422',
            'SKAA1422',
            'SHAR1023',
            'SHAR1033',
            'SHAR1043',
            'SHAY2023',
            'SHAR2103',
            'SHAR3053',
            'SHAR3113',
            'SHAR2033',
            'SHAR3173',
            'SHAR3033',
            'SHAR2043',
            'SHAR3163',
            'SHAY2063',
            'SHAY2013',
            'SHAY3013',
            'SHAR1053',
            'SHAD1033',
            'SHAR1063',
            'SHAR2023',
            'SHAD2023',
            'SHAR1083',
            'SHAR2053',
            'SHAR3013',
            'SHAY3023',
            'SHAR3103',
            'SHAR3063',
            'SHAR4013',
            'SHAY3043',
            'SHAR3023',
            'SHAY2103',
            'SHMR1023',
            'SHMR1033',
            'SHAR1093',
            'SHMR1093',
            'SHMY2023',
            'SHMR2103',
            'SHAR2113',
            'SHMR2033',
            'SHAY1073',
            'SHMR3163',
            'SHMY2063',
            'SHMY2013',
            'SHMR1053',
            'SHMR1063',
            'SHMR2023',
            'SHMR1083',
            'SHMR2053',
            'SHMR3013',
            'SHMY3023',
            'SHMR4013',
            'SHMY3043',
            'SSCE1693',
            'SSCE1793',
            'SSCE1993',
            'SSCE2193',
            'SSCE2393',
            'SSCM1803',
            'SSCC3423',
            'SCSR3104',
            'SCSJ3104',
            'SCSV3104'
            'SHMY2103'
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
        UPPER(b.stuMetricNo) AS [passwd],
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
        LTRIM(RTRIM(a.subjCode)) + '_' + a.section + '_' + SUBSTRING(d.sesName, 3, 2) +  SUBSTRING(d.sesName, 8, 2) + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode = '04' THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        LTRIM(RTRIM(a.subjCode)) + '_' + a.section + '_' + SUBSTRING(d.sesName, 3, 2) +  SUBSTRING(d.sesName, 8, 2) + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode = '04' THEN 'KL'
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
        SELECT LTRIM(RTRIM(a.subjCode)) + '_' + a.section + '_' + SUBSTRING(b.sesName, 3, 2) +  SUBSTRING(b.sesName, 8, 2) + RIGHT('00' + CAST(b.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode = '04' THEN 'KL'
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
        LTRIM(RTRIM(a.subjCode)) + '_' + a.section + '_' + SUBSTRING(c.sesName, 3, 2) +  SUBSTRING(c.sesName, 8, 2) + RIGHT('00' + CAST(c.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode = '04' THEN 'KL'
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

        // Upload with lowercase
        $sql = "
        SELECT LOWER(b.stuMetricNo) AS external_person_key,
        LTRIM(RTRIM(a.subjCode)) + '_' + a.section + '_' + SUBSTRING(c.sesName, 3, 2) +  SUBSTRING(c.sesName, 8, 2) + RIGHT('00' + CAST(c.semNo AS VARCHAR(2)), 2) + '_PS_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode = '04' THEN 'KL'
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