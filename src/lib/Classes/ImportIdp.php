<?php

namespace App\Classes;

use App\Interfaces\AbstractImport;
use App\Services\SqlServerService;

class ImportIdp extends AbstractImport
{
    protected $connection;
    protected $courses;

    public function __construct()
    {
        $serverName = $_ENV['SQL_SERVER_HOST'];
        $username = $_ENV['SQL_SERVER_USER'];
        $password = $_ENV['SQL_SERVER_PASSWORD'];

        $this->connection = SqlServerService::getConnection($serverName, $username, $password, 'SPACEDB1000IDP');

        $this->courses = "
            'BRG',
            'SSPB',
            'SSPG'
        ";
    }

    public function processLecturers()
    {
        $sql = "
        SELECT DISTINCT a.lecID AS external_person_key,
        'IDP_' + REPLACE(c.sesName, '/', '') + CAST(c.semNo AS VARCHAR) AS data_source_key,
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
        ) c ON c.sesSemNo = b.sesSemNo
        WHERE EXISTS (
            SELECT *
            FROM StuRegSubj a1
            JOIN Stu b1 ON b1.stuRef = a1.stuRef
            WHERE a1.subjCode = b.subjCode
            AND a1.sesSemNo = b.sesSemNo
            AND a1.section = b.section
            AND a1.centerCode = b.centerCode
            AND b1.courseCode IN ({$this->courses})
        )";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processStudents()
    {
        $sql = "
        SELECT DISTINCT UPPER(b.stuMetricNo) AS external_person_key,
        'UTMIDP_' + REPLACE(c.sesName, '/', '') + CAST(c.semNo AS VARCHAR) AS data_source_key,
        UPPER(b.stuName) AS firstname,
        '' AS lastname,
        UPPER(b.stuMetricNo) AS [user_id],
        b.stuICNo AS [passwd],
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
        ) c ON c.sesSemNo = a.sesSemNo
        JOIN Stu d ON d.stuRef = a.stuRef AND d.courseCode IN ({$this->courses})";

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
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        a.subjCode + '_' + a.section + '_' + SUBSTRING(d.sesName, 3, 2) +  SUBSTRING(d.sesName, 8, 2) + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS course_id,
        'SEM ' + SUBSTRING(d.sesName, 3, 2) + SUBSTRING(d.sesName, 8, 2) + '-' + RIGHT('00' + CAST(d.semNo AS VARCHAR(2)), 2) + ': ' + UPPER(b.subjNameBI) AS course_name,
        'UTMIDP_' + REPLACE(d.sesName, '/', '') + CAST(d.semNo AS VARCHAR) AS data_source_key
        FROM StuRegSubj a
        INNER JOIN Subj b ON b.subjCode = a.subjCode 
        INNER JOIN Fac c ON c.facCode = b.facCode
        INNER JOIN SesSem d ON d.sesSemNo = a.sesSemNo AND GETDATE() BETWEEN d.semStartDate AND d.lectureEndDate
        INNER JOIN Stu e ON e.stuRef = a.stuRef AND e.courseCode IN ({$this->courses})";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'course');
    }

    public function processCourseLecturers()
    {
        $sql = "
        SELECT a.subjCode + '_' + a.section + '_' + SUBSTRING(b.sesName, 3, 2) +  SUBSTRING(b.sesName, 8, 2) + RIGHT('00' + CAST(b.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        a.lecID AS external_person_key,
        'instructor' AS [role],
        'UTMIDP_' + REPLACE(b.sesName, '/', '') + CAST(b.semNo AS VARCHAR) AS data_source_key
        FROM SubjOffered a
        JOIN SesSem b ON b.sesSemNo = a.sesSemNo AND GETDATE() BETWEEN b.semStartDate AND b.lectureEndDate
        WHERE RTRIM(LTRIM(a.lecID)) IS NOT NULL
        AND EXISTS (
            SELECT *
            FROM StuRegSubj a1
            JOIN Stu b1 ON b1.stuRef = a1.stuRef
            WHERE a1.subjCode = a.subjCode
            AND a1.sesSemNo = a.sesSemNo
            AND a1.section = a.section
            AND a1.centerCode = a.centerCode
            AND b1.courseCode IN ({$this->courses})
        )";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }

    public function processEnrollments()
    {
        $sql = "
        SELECT b.stuMetricNo AS external_person_key,
        a.subjCode + '_' + a.section + '_' + SUBSTRING(c.sesName, 3, 2) +  SUBSTRING(c.sesName, 8, 2) + RIGHT('00' + CAST(c.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        'student' AS [role],
        'UTMIDP_' + REPLACE(c.sesName, '/', '') + CAST(c.semNo AS VARCHAR) AS data_source_key
        FROM StuRegSubj a
        JOIN Main b ON b.stuRef = a.stuRef
        JOIN SesSem c ON c.sesSemNo = a.sesSemNo AND GETDATE() BETWEEN c.semStartDate AND c.lectureEndDate
        JOIN Stu d ON d.stuRef = a.stuRef AND d.courseCode IN ({$this->courses})";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }
}