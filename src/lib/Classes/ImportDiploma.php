<?php

namespace App\Classes;

use App\Interfaces\AbstractImport;
use App\Services\SqlServerService;

class ImportDiploma extends AbstractImport
{
    protected $connection;
    protected $latestSemester;

    public function __construct()
    {
        $serverName = $_ENV['SQL_SERVER_AIMS_HOST'];
        $username = $_ENV['SQL_SERVER_AIMS_USER'];
        $password = $_ENV['SQL_SERVER_AIMS_PASSWORD'];

        $this->connection = SqlServerService::getConnection($serverName, $username, $password, 'AIMSDB');
        
        // $this->latestSemester = $this->getLatestSemester();
        // Harcode semester
        $this->latestSemester = '202120221';
    }

    private function getLatestSemester()
    {
        $sql = "
        SELECT TOP 1 SEMESTER AS semester
        FROM VW_UTMSPACE_COURSE_LECTURER
        ORDER BY SEMESTER DESC";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetch();

        return $results['semester'];
    }

    public function processLecturers()
    {
        $sql = "
        SELECT
        SUBSTRING(ISNULL(EMAIL_RASMI, EMAIL_KEDUA), 1, CHARINDEX('@', ISNULL(EMAIL_RASMI, EMAIL_KEDUA)) - 1) AS external_person_key,
        'DIPLOMA_{$this->latestSemester}' AS data_source_key,
        UPPER(NAMA) AS firstname,
        '' AS lastname,
        SUBSTRING(ISNULL(EMAIL_RASMI, EMAIL_KEDUA), 1, CHARINDEX('@', ISNULL(EMAIL_RASMI, EMAIL_KEDUA)) - 1) AS [user_id],
        NO_PEKERJA AS [passwd],
        'Y' AS available_ind,
        ISNULL(EMAIL_RASMI, EMAIL_KEDUA) AS email,
        'staff' AS institution_role
        FROM VW_UTMSPACE_LECTURER a
        WHERE ISNULL(EMAIL_RASMI, EMAIL_KEDUA) IS NOT NULL
        AND EXISTS (
            SELECT *
            FROM VW_UTMSPACE_COURSE_LECTURER
            WHERE NO_PEKERJA = a.NO_PEKERJA
            AND SEMESTER = '{$this->latestSemester}'
        )";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processStudents()
    {
        $sql = "
        SELECT NO_MATRIK AS external_person_key,
        'DIPLOMA_{$this->latestSemester}' AS data_source_key,
        UPPER(NAMA) AS firstname,
        '' AS lastname,
        NO_MATRIK AS [user_id],
        NO_MATRIK AS [passwd],
        'Y' AS available_ind,
        ISNULL(EMAIL_RASMI, EMAIL) AS email,
        'student' AS institution_role
        FROM VW_UTMSPACE_STUDENT a
        WHERE EXISTS (
            SELECT *
            FROM VW_UTMSPACE_COURSE_STUDENT
            WHERE NO_MATRIK = a.NO_MATRIK
            AND SEMESTER = '{$this->latestSemester}'
        )";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'person');
    }

    public function processCourses()
    {
        $sql = "
        SELECT
        KOD_KURSUS + '_' + SEKSYEN + '_' + SUBSTRING(SEMESTER, 3, 2) + SUBSTRING(SEMESTER, 7, 2) + RIGHT('00' + ISNULL(SUBSTRING(SEMESTER, 9, 1), ''), 2) + '_AD_KL' AS external_course_key,
        KOD_KURSUS + '_' + SEKSYEN + '_' + SUBSTRING(SEMESTER, 3, 2) + SUBSTRING(SEMESTER, 7, 2) + RIGHT('00' + ISNULL(SUBSTRING(SEMESTER, 9, 1), ''), 2) + '_AD_KL' AS course_id,
        'SEM ' + SUBSTRING(SEMESTER, 3, 2) + SUBSTRING(SEMESTER, 7, 2) + '-' + SUBSTRING(SEMESTER, 8, 1) + ': ' + UPPER(NAMA_KURSUS) AS course_name,
        'DIPLOMA_{$this->latestSemester}' AS data_source_key
        FROM VW_UTMSPACE_COURSE a
        WHERE EXISTS (
            SELECT *
            FROM VW_UTMSPACE_COURSE_LECTURER
            WHERE KOD_KURSUS = a.KOD_KURSUS
            AND SEKSYEN = a.SEKSYEN
            AND SEMESTER = a.SEMESTER
        )
        AND a.SEMESTER = '{$this->latestSemester}'";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'course');
    }

    public function processCourseLecturers()
    {
        $sql = "
        SELECT
        KOD_KURSUS + '_' + SEKSYEN + '_' + SUBSTRING(SEMESTER, 3, 2) + SUBSTRING(SEMESTER, 7, 2) + RIGHT('00' + ISNULL(SUBSTRING(SEMESTER, 9, 1), ''), 2) + '_AD_KL' AS external_course_key,
        SUBSTRING(ISNULL(EMAIL_RASMI, EMAIL_KEDUA), 1, CHARINDEX('@', ISNULL(EMAIL_RASMI, EMAIL_KEDUA)) - 1) AS external_person_key,
        'instructor' AS [role],
        'DIPLOMA_{$this->latestSemester}' AS data_source_key
        FROM VW_UTMSPACE_COURSE_LECTURER a
        JOIN VW_UTMSPACE_LECTURER b ON b.NO_PEKERJA = a.NO_PEKERJA
        WHERE ISNULL(EMAIL_RASMI, EMAIL_KEDUA) IS NOT NULL
        AND a.SEMESTER = '{$this->latestSemester}'";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        echo 'Semester: ' . $this->latestSemester . "\n";

        $this->upload($results, 'membership');
    }

    public function processEnrollments()
    {
        $sql = "
        SELECT
        a.KOD_KURSUS + '_' + a.SEKSYEN + '_' + SUBSTRING(a.SEMESTER, 3, 2) + SUBSTRING(a.SEMESTER, 7, 2) + RIGHT('00' + ISNULL(SUBSTRING(a.SEMESTER, 9, 1), ''), 2) + '_AD_KL' AS external_course_key,
        a.NO_MATRIK AS external_person_key,
        'student' AS [role],
        'DIPLOMA_{$this->latestSemester}' AS data_source_key
        FROM VW_UTMSPACE_COURSE_STUDENT a
        JOIN VW_UTMSPACE_COURSE_LECTURER b on b.KOD_KURSUS = a.KOD_KURSUS AND b.SEKSYEN = a.SEKSYEN AND b.SEMESTER = a.SEMESTER
        WHERE a.SEMESTER = '{$this->latestSemester}'";

        $stmt = $this->connection->query($sql);
        $results = $stmt->fetchAll();

        $this->upload($results, 'membership');
    }
}