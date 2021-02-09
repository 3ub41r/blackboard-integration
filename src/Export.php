<?php

class Export
{
    protected $serverName;
    protected $database;
    protected $username;
    protected $password;
    protected $connection;
    protected $datasourceKey;

    const GENERATED_PATH = '../data';
    const DATASOURCE_KEY = 'testsis';
    const SEPARATOR = '|';
    const PDO_OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    public function __construct($serverName, $username, $password, $database, $datasourceKey = self::DATASOURCE_KEY)
    {
        $this->serverName = $serverName;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->datasourceKey = $datasourceKey;

        $this->connection = new PDO("sqlsrv:server=$this->serverName;Database=$this->database", $this->username, $this->password, self::PDO_OPTIONS);
    }

    public function generateFile($results, $output)
    {
        if (! $results || empty($results)) {
            echo "No results for $output...\n";
            return null;
        }

        if (! file_exists(self::GENERATED_PATH)) {
            mkdir(self::GENERATED_PATH, 0777, true);
        }

        $output = self::GENERATED_PATH . "/$output";

        echo "Writing to $output...\n";

        $text = implode(self::SEPARATOR, array_keys($results[0])) . "\n";

        foreach ($results as $result) {
            // Trim
            foreach ($result as $key => $value) {
                $result[$key] = trim($value);
            }

            $text .= implode(self::SEPARATOR, $result) . "\n";
        }

        file_put_contents($output, $text);
    }

    public function processAll()
    {
        $this->processLecturers();
        $this->processStudents();
        $this->processCourses();
        $this->processEnrollments();
        $this->processSubjectLecturers();
    }

    /**
     * Get lecturers teaching the current semester.
     */
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
        $this->generateFile($stmt->fetchAll(), 'lecturers.txt');
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
        $this->generateFile($stmt->fetchAll(), 'students.txt');
    }

    public function processCourses()
    {
        $sql = "SELECT Kod_seksyen AS external_course_key,
        Kod_seksyen AS course_id,
        Namabi AS course_name,
        '$this->datasourceKey' AS data_source_key 
        FROM ELEARNING_COURSE";

        $stmt = $this->connection->query($sql);
        $this->generateFile($stmt->fetchAll(), 'courses.txt');
    }

    public function processEnrollments()
    {
        $sql = "SELECT Matrik AS external_person_key,
        Kod AS external_course_key,
        'student' AS [role],
        '$this->datasourceKey' AS data_source_key
        FROM ELEARNING_STUDENT_COURSE";

        $stmt = $this->connection->query($sql);
        $this->generateFile($stmt->fetchAll(), 'enrollments.txt');
    }

    public function processSubjectLecturers()
    {
        $sql = "SELECT Matrik AS external_person_key,
        Kod AS external_course_key,
        'instructor' AS [role],
        '$this->datasourceKey' AS data_source_key
        FROM ELEARNING_LECTURER_COURSE";

        $stmt = $this->connection->query($sql);
        $this->generateFile($stmt->fetchAll(), 'courselecturers.txt');
    }
}