<?php

enum Type: string {
    case name = "name";
    case nickname = "nickname";
    case mail = "mail";
    case password = "password";
    case passwordOrEmpty = "passwordOrEmpty";
    case date = "date";
    case string = "string";
    case number = "number";
    case code = "code";
}

enum DataType {
    case array;
    case int_array;
    case json;
    case string;
    case int;
}

class Validator {
    private static array $regexStrings = [
        "name" => "^[a-žA-Ž ]{3,100}$",
        "nickname" => "^[a-žA-Ž0-9_]{5,100}$",
        "mail" => "^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$",
        "password" => "(?=(.*[0-9]))((?=.*[a-žA-Ž0-9])(?=.*[A-Ž])(?=.*[a-ž]))^.{8,}$",
        "passwordOrEmpty" => "^$|(?=(.*[0-9]))((?=.*[a-žA-Ž0-9])(?=.*[A-Ž])(?=.*[a-ž]))^.{8,}$",
        "date" => "^\d{4}-\d{1,2}-\d{1,2}$",
        "code" => "^[A-Z0-9]{20}$",
    ];

    public static function validate(string $value, Type $type) {
        return preg_match("/" . self::$regexStrings[$type->value] . "/", $value) == 1;
    }

    public static function validateDataType($data, DataType $DataType) {
        switch ($DataType) {
            case DataType::array:
                return is_array($data);
            case DataType::int_array:
                return is_array($data) && empty(array_filter($data, fn ($e) => !is_numeric($e)));
            case DataType::json:
                return is_json($data);
            case DataType::string:
                return is_string($data);
            case DataType::int:
                return is_int($data);
            default:
                $response = new ApiErrorResponse("Invalid data type " . $DataType->value);
                $response->send();
        }
    }

    public static function generateJsValues() {
        /** @var Type $key */
        $output = "const REGEXES = {";
        foreach (self::$regexStrings as $key => $value) {
            $output .= $key . ": new RegExp(\""  . $value . "\"),\n";
        }
        $output .= "};";

        echo $output;
    }

    public static function existsIn(string $tableName): ApiEndpointCondition {
        global $con;
        return new ApiEndpointCondition(function (string $id) use ($con, $tableName) {
            return !empty($con->select("id", $tableName)->where(["id" => $id])->fetchRow());
        }, new ApiErrorResponse(""));
    }
}
