<?php

class Upload
{
    protected $source;
    protected $endpointUrl;

    /**
     * Upload exported flat files to Blackboard
     *
     * @param string $source Path to flat files
     * @param string $endpointUrl URL to Blackboard SIS
     */
    public function __construct($source, $endpointUrl)
    {
        $this->source = $source;
        $this->endpointUrl = $endpointUrl;
    }

    /**
     * Get feed type of file
     *
     * @param string $file
     * @return void
     */
    protected function getFeedType($file)
    {
        $feedMap = [
            'external_person_key' => 'person',
            'external_course_key' => 'course',
        ];

        // Read first line of file
        $line = fgets(fopen($file, 'r'));

        foreach ($feedMap as $key => $value) {
            if (strpos($line, $key) !== false) {
                return $value;
            }
        }

        return null;
    }

    public function process()
    {
        // Traverse files in directory
        $files = scandir($this->source);

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) continue;

            $feedType = $this->getFeedType($file);

            // Build URL
            $url = $this->endpointUrl . "/$feedType/store";

            
        }

        // Determine type of file

        // Upload to SIS

    }
}