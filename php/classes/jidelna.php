<?php

class Jidelna {
    private Conn $con;

    private array $cache;
    private array $cachedDays;

    public function __construct() {
        global $con;
        $this->con = $con;

        $this->cachedDays = array_map(fn ($e) => $e["date"], $this->con->query("SELECT date FROM jidelna_cache")->fetchAll());
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

        foreach ($data as $data_day) {
            if (empty($data_day)) continue;

            $transformedData = $this->transformData($data_day);

            if (empty($transformedData)) continue;

            $date = new DateTime($data_day[0]["datum"]);

            if ($date == $day) $output = $transformedData;

            $this->writeToCache($date, $transformedData);
        }

        return $output;
    }

    private function readFromCache(DateTime $day): array {
        return $this->cache[$day->format("Y-m-d")] ?? (in_array($day->format("Y-m-d"), $this->cachedDays)) ? json_decode($this->con->query("SELECT data FROM jidelna_cache WHERE date = ", [$day->format("Y-m-d")])->fetchRow()["data"], true) : [];
    }

    private function writeToCache(DateTime $day, array $data) {
        $this->cache[$day->format("Y-m-d")] = $data;
        if (in_array($day->format("Y-m-d"), $this->cachedDays)) return;
        $this->cachedDays[] = $day->format("Y-m-d");
        $this->con->query("INSERT INTO jidelna_cache (date, data) VALUES (", [$day->format("Y-m-d")], ", ", [utf8json($data)], ")");
    }

    private function transformData(array $data): array {
        $output = [];
        $interested_in = ["O1", "O2", "O3", "SV", "X1"];
        foreach ($data as $data_row) {
            if (!in_array($data_row["druh"], $interested_in)) continue;
            $output[$data_row["druh"]] = $data_row["nazev"];
        }
        return $output;
    }
}
