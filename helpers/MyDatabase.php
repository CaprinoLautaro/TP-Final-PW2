<?php

class MyDatabase
{
    private $conexion;

    public function __construct($hostname, $username, $password, $database)
    {
        $this->conexion = new mysqli(
            $hostname,
            $username,
            $password,
            $database,
        );

        $this->conexion->set_charset("utf8mb4");
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param(
                str_repeat('s', count($params)),
                ...$params
            );
        }

        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    public function execute($sql, $params = [])
    {
        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            die(
                "Error en prepare: " .
                $this->conexion->error
            );
        }

        if (!empty($params)) {

            $types =
                str_repeat(
                    "s",
                    count($params)
                );

            $stmt->bind_param(
                $types,
                ...$params
            );
        }

        if (!$stmt->execute()) {

            die(
                "Error SQL: " .
                $stmt->error
            );
        }

        return $stmt->affected_rows;
    }

    public function lastInsertId()
    {
        return $this->conexion->insert_id;
    }

    public function __destruct()
    {
        $this->conexion->close();
    }
}