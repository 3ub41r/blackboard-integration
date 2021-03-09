<?php

namespace App\Classes;

use App\Interfaces\AbstractImport;
use App\Services\SqlServerService;

class ImportFoundation extends AbstractImport
{
    protected $connection;

    public function __construct($datasourceKey)
    {
        parent::__construct($datasourceKey);

        $serverName = $_ENV['SQL_SERVER_HOST'];
        $username = $_ENV['SQL_SERVER_USER'];
        $password = $_ENV['SQL_SERVER_PASSWORD'];

        $this->connection = SqlServerService::getConnection($serverName, $username, $password, 'SPACEDB1000Foundation');
    }

    public function processLecturers()
    {
        $sql = "
        SELECT Matrik AS external_person_key,
        '$this->datasourceKey' AS data_source_key,
        UPPER(Nama) AS firstname,
        '' AS lastname,
        Matrik AS [user_id],
        Nokp AS passwd,
        'Y' AS available_ind,
        Email AS email,
        'staff' AS institution_role 
        FROM ELEARNING_PENSYARAH
        WHERE Matrik IS NOT NULL AND RTRIM(LTRIM(Matrik)) <> ''";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processStudents()
    {
        $sql = "
        SELECT Matrik AS external_person_key,
        '$this->datasourceKey' AS data_source_key,
        UPPER(Nama) AS firstname,
        '' AS lastname,
        Matrik AS [user_id],
        Matrik AS passwd,
        'Y' AS available_ind,
        Email AS email,
        'student' AS institution_role 
        FROM ELEARNING_PELAJAR
        WHERE Matrik IS NOT NULL AND RTRIM(LTRIM(Matrik)) <> ''";

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
        '$this->datasourceKey' AS data_source_key
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
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        a.lecID AS external_person_key,
        'instructor' AS [role],
        '$this->datasourceKey' AS data_source_key
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
        SELECT b.stuMetricNo AS external_person_key,
        a.subjCode + '_' + a.section + '_' + SUBSTRING(c.sesName, 3, 2) +  SUBSTRING(c.sesName, 8, 2) + RIGHT('00' + CAST(c.semNo AS VARCHAR(2)), 2) + '_AF_' + 
        CASE 
            WHEN a.centerCode = '01' THEN 'JB'
            WHEN a.centerCode IN ('04', '05') THEN 'KL'
            ELSE a.centerCode
        END AS external_course_key,
        'student' AS [role],
        '$this->datasourceKey' AS data_source_key
        FROM StuRegSubj a
        JOIN Main b ON b.stuRef = a.stuRef
        JOIN SesSem c ON c.sesSemNo = a.sesSemNo AND GETDATE() BETWEEN c.semStartDate AND c.lectureEndDate";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }
}