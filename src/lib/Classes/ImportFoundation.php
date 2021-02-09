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
        $sql = "SELECT Matrik AS external_person_key,
        '$this->datasourceKey' AS data_source_key,
        Nama AS firstname,
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
        $sql = "SELECT Matrik AS external_person_key,
        '$this->datasourceKey' AS data_source_key,
        Nama AS firstname,
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
        $sql = "SELECT Kod_seksyen AS external_course_key,
        Kod_seksyen AS course_id,
        Namabi AS course_name,
        '$this->datasourceKey' AS data_source_key 
        FROM ELEARNING_COURSE";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'course');
    }

    public function processSubjectLecturers()
    {
        $sql = "SELECT Matrik AS external_person_key,
        Kod AS external_course_key,
        'instructor' AS [role],
        '$this->datasourceKey' AS data_source_key
        FROM ELEARNING_LECTURER_COURSE";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }

    public function processEnrollments()
    {
        $sql = "SELECT Matrik AS external_person_key,
        Kod AS external_course_key,
        'student' AS [role],
        '$this->datasourceKey' AS data_source_key
        FROM ELEARNING_STUDENT_COURSE";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }
}