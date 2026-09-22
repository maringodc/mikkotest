<?php
class Pay {

    private string $filename = "paydates-";

    public function retrieveCSV(): string
    {
        $now = new DateTime();
        $currentMonth = $now->format('Ym');
        $fullFilename = $this->filename . $currentMonth . ".csv";
        if (!file_exists($fullFilename)) {
            $data = $this->createData();
            $this->writeCSV($data, $fullFilename);
        }
        return $fullFilename;
    }

    private function createData(): array
    {
        $data = [];
        $now = new DateTime();
        $year = (int)$now->format('Y');
        for($month = (int)$now->format('n'); $month <= 12; $month++) {
            $monthData = [];
            $monthData[] = DateTime::createFromFormat('!m', $month)->format('F');
            try {
                $monthData[] = $this->getSalaryDay($year, $month)->format('d-m-Y');
                $monthData[] = $this->getBonusDay($year, $month)->format('d-m-Y');
            }
            catch (\Exception $e) {
                echo "Error on month " . $month . ". Message: " . $e->getMessage();
                die;
            }
            $data[] = $monthData;
        }
        return $data;
    }

    private function writeCSV($data, $fullFilename): void
    {
        $file = fopen($fullFilename, "a");
        foreach($data as $row) {
            fputcsv($file, $row, ";");
        }
        fclose($file);
    }

    /**
     * @throws DateMalformedStringException
     */
    private function getBonusDay(int $year, int $month): DateTime|false
    {
        $bonusDay = DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-15');
        $bonusDay->modify('+1 month');
        $bonusDayWeekday = (int)$bonusDay->format('N');
        if($bonusDayWeekday >= 6){
            $bonusDay->add(DateInterval::createFromDateString(10 - $bonusDayWeekday . ' day'));
        }
        return $bonusDay;
    }

    /**
     * @throws DateInvalidOperationException
     * @throws DateMalformedStringException
     */
    private function getSalaryDay(int $year, int $month): DateTime|false
    {
        $salaryDay = DateTime::createFromFormat('Y-m', $year . '-' . $month)->modify('last day of this month');
        $salaryDayWeekday = (int)$salaryDay->format('N');
        if($salaryDayWeekday >= 6){
            $salaryDay->sub(DateInterval::createFromDateString((-5 + $salaryDayWeekday) . ' day'));
        }
        return $salaryDay;
    }
}

$pay = new Pay();
$filename = $pay->retrieveCSV();

header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . basename($filename) . "\"");
readfile($filename);
