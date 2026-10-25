<?php

class Jidelna {
    private Conn $con;
    private const MAX_DAYS_PRECACHE = 7;
    private array $cachedDays;

    public function __construct() {
        global $con;
        $this->con = $con;
        $this->cachedDays = $this->con
            ->select("date", "jidelna_cache")
            ->fetchColumn();
    }

    public function fetchDay(DateTime $day) {
        if ($day->format("N") > 5) {
            return ["result" => "error"];
        }

        $cacheHit = $this->readFromCache($day);
        if (!empty($cacheHit)) {
            return $cacheHit;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.strava.cz/strava5/Jidelnicky/XML?zarizeni=1692");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            return ["result" => "error"];
        }

        $data = simplexml_load_string($result);
        if ($data === false) {
            return ["result" => "error"];
        }

        $output = ["result" => "error"];
        $cacheTill = new DateTime("today");
        $cacheTill->modify("+" . self::MAX_DAYS_PRECACHE . " days");

        foreach ($data->den as $data_day) {
            $date = DateTime::createFromFormat("!d-m-Y", (string) $data_day["datum"]);
            if (!$date) {
                continue;
            }

            $transformedData = $this->transformData($data_day);

            if ($date->format("Y-m-d") === $day->format("Y-m-d")) {
                $output = $transformedData;
            }

            if ($date <= $cacheTill) {
                $this->writeToCache($date, $transformedData);
            }
        }

        return $output;
    }

    private function readFromCache(DateTime $day): ?array {
        return in_array($day->format("Y-m-d"), $this->cachedDays)
            ? json_decode(
                $this->con->query(
                    "SELECT data FROM jidelna_cache WHERE date = ",
                    [$day->format("Y-m-d")]
                )->fetchRow()["data"],
                true
            )
            : [];
    }

    private function writeToCache(DateTime $day, array $data) {
        if (in_array($day->format("Y-m-d"), $this->cachedDays)) {
            return;
        }

        $this->cachedDays[] = $day->format("Y-m-d");
        $this->con->insert("jidelna_cache", [
            "date" => $day->format("Y-m-d"),
            "data" => utf8json($data)
        ]);
    }

    private function transformData(SimpleXMLElement $data): array {
        $output = [
            "O1" => "Není",
            "O2" => "Není",
            "O3" => "Není",
            "SV" => "Není",
            "X1" => "Není"
        ];

        foreach ($data->jidlo as $food) {
            $kind = trim((string) $food["druh"]);
            $name = trim((string) $food["nazev"]);

            if (in_array($kind, ["Oběd 1", "Oběd 1 1"])) {
                $output["O1"] = $name;
            } elseif (in_array($kind, ["Oběd 2", "Oběd 2 1"])) {
                $output["O2"] = $name;
            } elseif (in_array($kind, ["Oběd 3", "Oběd 3 1"])) {
                $output["O3"] = $name;
            } elseif ($kind === "Svačina") {
                $output["SV"] = $name;
            } elseif ($kind === "Polévka") {
                $output["X1"] = $name;
            }
        }

        return $output;
    }
}
