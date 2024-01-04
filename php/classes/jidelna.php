<?php

class Jidelna {
    private Conn $con;

    private const MAX_DAYS_PRECACHE = 7;

    private array $cachedDays;

    public function __construct() {
        global $con;
        $this->con = $con;

        $this->cachedDays = $this->con->select("date", "jidelna_cache")->fetchColumn();
    }

    public function fetchDay(DateTime $day) {
        if ($day->format("N") > 5) return ["result" => "error"];

        $cacheHit = $this->readFromCache($day);
        if (!empty($cacheHit)) return $cacheHit;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.strava.cz/api/jidelnicky");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"cislo":"1692"}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));
        $result = curl_exec($ch);

        try {
            $data = json_decode($result, true);
        } catch (Exception $e) {
            return ["result" => "error"];
        }

        if (isset($data["state"]) && $data["state"] == "error") return ["result" => "error"];

        $output = ["result" => "error"];

        $cacheTill = new DateTime("today");
        $cacheTill->modify("+" . self::MAX_DAYS_PRECACHE . "days");

        foreach ($data as $data_day) {
            if (empty($data_day)) continue;

            $transformedData = $this->transformData($data_day);

            if (empty($transformedData)) continue;

            $date = new DateTime($data_day[0]["datum"]);

            if ($date == $day) $output = $transformedData;

            if ($date <= $cacheTill)
                $this->writeToCache($date, $transformedData);
        }

        return $output;
    }

    private function readFromCache(DateTime $day): ?array {
        return (in_array($day->format("Y-m-d"), $this->cachedDays)) ? json_decode($this->con->query("SELECT data FROM jidelna_cache WHERE date = ", [$day->format("Y-m-d")])->fetchRow()["data"], true) : [];
    }

    private function writeToCache(DateTime $day, array $data) {
        if (in_array($day->format("Y-m-d"), $this->cachedDays)) return;
        $this->cachedDays[] = $day->format("Y-m-d");
        $this->con->insert("jidelna_cache", ["date" => $day->format("Y-m-d"), "data" => utf8json($data)]);
    }

    private function transformData(array $data): array {
        $output = ["O1" => "Není", "O2" => "Není", "O3" => "Není", "SV" => "Není", "X1" => "Není"];
        foreach ($data as $data_row) {
            if (!isset($output[$data_row["druh"]])) continue;
            $output[$data_row["druh"]] = $data_row["nazev"];
        }
        return $output;
    }
}
