<?php

namespace App\Classes;

use App\Interfaces\AbstractImport;
use App\Services\SqlServerService;

class ImportFoundation extends AbstractImport
{
    protected $connection;

    public function __construct()
    {
        $serverName = $_ENV['SQL_SERVER_HOST'];
        $username = $_ENV['SQL_SERVER_USER'];
        $password = $_ENV['SQL_SERVER_PASSWORD'];

        $this->connection = SqlServerService::getConnection($serverName, $username, $password, 'SPACEDB1000Foundation');
    }

    public function processLecturers()
    {
        $sql = "
        SELECT DISTINCT a.lecID AS external_person_key,
        'FOUNDATION_' + REPLACE(c.sesName, '/', '') + CAST(c.semNo AS VARCHAR) AS data_source_key,
        UPPER(a.lecName) AS firstname,
        '' AS lastname,
        UPPER(a.lecID) AS [user_id],
        a.lecICNo AS passwd,
        'Y' AS available_ind,
        email,
        'staff' AS institution_role
        FROM Lecturer a
        JOIN SubjOffered b ON b.lecID = a.lecID
        JOIN (
            SELECT TOP 1 *
            FROM SesSem
            WHERE GETDATE() BETWEEN semStartDate AND lectureEndDate
            ORDER BY sesSemNo DESC
        ) c ON c.sesSemNo = b.sesSemNo";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processStudents()
    {
        $sql = "
        SELECT DISTINCT UPPER(b.matricNumber) AS external_person_key,
        'FOUNDATION_' + REPLACE(c.sesName, '/', '') + CAST(c.semNo AS VARCHAR) AS data_source_key,
        UPPER(b.stuName) AS firstname,
        '' AS lastname,
        UPPER(b.matricNumber) AS [user_id],
        b.stuMetricNo AS [passwd],
        'Y' AS available_ind,
        b.eMail AS email,
        'student' AS institution_role 
        FROM StuRegSubj a
        JOIN Main b ON b.stuRef = a.stuRef
        JOIN (
            SELECT TOP 1 *
            FROM SesSem
            WHERE GETDATE() BETWEEN semStartDate AND lectureEndDate
            ORDER BY sesSemNo DESC
        ) c ON c.sesSemNo = a.sesSemNo";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processCourses()
    {
        $sql = "
        SELECT DISTINCT 
        a.subjCode + '_' + a.section + '_' + SUBSTRING(d.sesName, 3, 2) +  SUBSTRING(d.sesName, 8, 2) + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode IN ('01', '04') THEN 'JB'
            WHEN a.centerCode IN ('05', '06') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        a.subjCode + '_' + a.section + '_' + SUBSTRING(d.sesName, 3, 2) +  SUBSTRING(d.sesName, 8, 2) + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode IN ('01', '04') THEN 'JB'
            WHEN a.centerCode IN ('05', '06') THEN 'KL'
            ELSE a.centerCode
        END AS course_id,
        'SEM ' + SUBSTRING(d.sesName, 3, 2) + SUBSTRING(d.sesName, 8, 2) + '-' + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + ': ' + UPPER(b.subjNameBI) AS course_name,
        'FOUNDATION_' + REPLACE(d.sesName, '/', '') + CAST(d.semNo AS VARCHAR) AS data_source_key
        FROM StuRegSubj a
        INNER JOIN Subj b ON b.subjCode = a.subjCode 
        INNER JOIN Fac c ON c.facCode = b.facCode
        INNER JOIN SesSem d ON d.sesSemNo = a.sesSemNo AND GETDATE() BETWEEN d.semStartDate AND d.lectureEndDate";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'course');
    }

    public function processCourseLecturers()
    {
        $sql = "
        SELECT a.subjCode + '_' + a.section + '_' + SUBSTRING(b.sesName, 3, 2) +  SUBSTRING(b.sesName, 8, 2) + RIGHT('00' + CAST(b.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode IN ('01', '04') THEN 'JB'
            WHEN a.centerCode IN ('05', '06') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        a.lecID AS external_person_key,
        'instructor' AS [role],
        'FOUNDATION_' + REPLACE(b.sesName, '/', '') + CAST(b.semNo AS VARCHAR) AS data_source_key
        FROM SubjOffered a
        JOIN SesSem b ON b.sesSemNo = a.sesSemNo AND GETDATE() BETWEEN b.semStartDate AND b.lectureEndDate
        WHERE RTRIM(LTRIM(a.lecID)) IS NOT NULL";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }

    public function processEnrollments()
    {
        $sql = "
        SELECT b.matricNumber AS external_person_key,
        a.subjCode + '_' + a.section + '_' + SUBSTRING(c.sesName, 3, 2) +  SUBSTRING(c.sesName, 8, 2) + RIGHT('00' + CAST(c.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode IN ('01', '04') THEN 'JB'
            WHEN a.centerCode IN ('05', '06') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        'student' AS [role],
        'FOUNDATION_' + REPLACE(c.sesName, '/', '') + CAST(c.semNo AS VARCHAR) AS data_source_key
        FROM StuRegSubj a
        JOIN Main b ON b.stuRef = a.stuRef
        JOIN SesSem c ON c.sesSemNo = a.sesSemNo AND GETDATE() BETWEEN c.semStartDate AND c.lectureEndDate";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }
}