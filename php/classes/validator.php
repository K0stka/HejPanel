<?php

enum Type: string {
    case name = "^[a-žA-Ž ]{3,100}$";
    case nickname = "^[a-žA-Ž0-9_]{5,100}$";
    case mail = "^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$";
    case nullableMail = "^([a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}){0,1}$";
    case password = "(?=(.*[0-9]))((?=.*[a-žA-Ž0-9])(?=.*[A-Ž])(?=.*[a-ž]))^.{8,}$";
    case passwordOrEmpty = "^$|(?=(.*[0-9]))((?=.*[a-žA-Ž0-9])(?=.*[A-Ž])(?=.*[a-ž]))^.{8,}$";
    case date = "^\d{4}-\d{1,2}-\d{1,2}$";
    case code = "^[A-Z0-9]{20}$";
    case nullableUrl = "^((http(s)?):\/\/[(www\.)?a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)){0,1}$";
}

enum DataType {
    case array;
    case int_array;
    case json;
    case string;
    case int;
}

class Validator {
    public static function validate(string $value, Type $type) {
        return preg_match("/" . $type->value . "/", $value) == 1;
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
                return is_numeric($data);
            default:
                printError("Tried to validate invalid data type", ["dataType" => $DataType->value]);
        }
    }

    public static function generateJsValues() {
        $output = "const REGEXES = {";
        /** @var Type $type */
        foreach (Type::cases() as $type) {
            $output .= $type->name . ": new RegExp(" . utf8json($type->value) . "),\n";
        }
        $output .= "};";

        echo $output;
    }

    public static function existsIn(string $tableName): ApiEndpointCondition {
        global $con;
        return new ApiEndpointCondition(function (string $id) use ($con, $tableName) {
            return !empty($con->select("id", $tableName)->where(["id" => $id])->fetchRow());
        }, new ApiErrorResponse(DEV ? "Given id does not exist in the table $tableName" : ""));
    }
}
