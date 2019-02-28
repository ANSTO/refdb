<?php
/**
 * Created by PhpStorm.
 * User: jdp
 * Date: 1/03/2019
 * Time: 10:14 AM
 */

namespace AppBundle\Http;


use Symfony\Component\HttpFoundation\Response;

class CsvResponse extends Response
{
    protected $data;
    protected $filename = 'export.csv';

    public function __construct($data = array(), $status = 200, $headers = array()) {
        parent::__construct('', $status, $headers);
        $this->setData($data);
    }

    public function setData(array $data) {
        $output = fopen('php://temp', 'r+');

        if (count($data) > 0) {
            $columns = array();
            foreach ($data as $row) {
                foreach (array_keys($row) as $key) {
                    if (!in_array($key,$columns)) {
                        $columns[] = $key;
                    }
                }
            }
            fputcsv($output, $columns);

            foreach ($data as $row) {
                $line = array();
                foreach ($columns as $column) {
                    if (isset($row[$column])) {
                        $line[$column] = $row[$column];
                    } else {
                        $line[$column] = null;
                    }
                }
                fputcsv($output, $line);
            }
        }
        rewind($output);
        $this->data = '';
        while ($line = fgets($output)) {
            $this->data .= $line;
        }
        $this->data .= fgets($output);
        return $this->update();
    }

    public function getFilename() {
        return $this->filename;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
        return $this->update();
    }

    protected function update() {
        $this->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $this->filename));
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'text/csv');
        }
        return $this->setContent($this->data);
    }
}