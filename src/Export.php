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

    public function __construct($serverName, $username, $password, $database = null)
    {
        $this->serverName = $serverName;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->datasourceKey = self::DATASOURCE_KEY;

        $this->connection = new PDO("sqlsrv:server=$this->serverName;Database=$this->database", $this->username, $this->password, self::PDO_OPTIONS);
    }

    public function setDatabase($database)
    {
        $this->database = $database;

        $this->connection->query("USE $database;");
        // $this->connection = new PDO("sqlsrv:server=$this->serverName;Database=$this->database", $this->username, $this->password, self::PDO_OPTIONS);
    }

    public function generateFile($results, $output)
    {
        if (! $results || empty($results)) return null;

        if (! file_exists(self::GENERATED_PATH)) {
            mkdir(self::GENERATED_PATH, 0777, true);
        }

        $output = self::GENERATED_PATH . "/$output";

        echo "Writing to $output...\n";

        $text = implode(self::SEPARATOR, array_keys($results[0])) . "\n";

        foreach ($results as $result) {
            $text .= implode(self::SEPARATOR, $result) . "\n";
        }

        file_put_contents($output, $text);
    }

    /**
     * Get lecturers teaching the current semester.
     */
    public function getLecturers()
    {
        $sql = "SELECT TOP 5 Matrik AS external_person_key,
        '$this->datasourceKey' AS data_source_key,
        Nama AS firstname,
        '' AS lastname,
        Matrik AS [user_id],
        Nokp AS passwd,
        'Y' AS available_ind,
        Email AS email,
        'staff' AS institution_role 
        FROM ELEARNING_PENSYARAH";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll();
    }

    public function getStudents()
    {
        $sql = "SELECT TOP 5 Matrik AS external_person_key,
        '$this->datasourceKey' AS data_source_key,
        Nama AS firstname,
        '' AS lastname,
        Matrik AS [user_id],
        Matrik AS passwd,
        'Y' AS available_ind,
        Email AS email,
        'student' AS institution_role 
        FROM ELEARNING_PELAJAR";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll();
    }

    public function getSubjects()
    {
        $sql = "SELECT TOP 5 Kod_seksyen AS external_course_key,
        Kod_seksyen AS course_id,
        Namabi AS course_name,
        '$this->datasourceKey' AS data_source_key 
        FROM ELEARNING_COURSE";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll();
    }

    public function getEnrollment()
    {
        $sql = "SELECT Kod AS course_id,
        Matrik AS [user_id],
        'S' AS course_role,
        'Y' AS system_available,
        'Y' AS course_available
        FROM ELEARNING_STUDENT_COURSE";

        $stmt = $this->connection->query($sql);

        return $stmt->fetchAll();
    }

    public function getSubjectLecturers()
    {
        // 
    }
}