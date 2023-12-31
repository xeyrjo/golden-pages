<?php

namespace Models;

class ActiveRecord {

        // Base de Datos
        protected static $db;
        protected static $tabla = '';
        protected static $idColumn;
        protected static $columnasDB = [];
        protected static $alertas = []; // Alertas y Mensajes
        
        // Definir la conexión a la BD - includes/database.php
        public static function setDB($database) {
            self::$db = $database;
        }
    
        public static function setAlerta($tipo, $name, $mensaje) {
            static::$alertas[$tipo][$name] = $mensaje;
        }
    
        // Validación
        public static function getAlertas() {
            return static::$alertas;
        }
    
        public function validar() {
            static::$alertas = [];
            return static::$alertas;
        }
    
        // Consulta SQL para crear un objeto en Memoria
        public static function consultarSQL($query) {
            // Consultar la base de datos
            $resultado = self::$db->query($query);
    
            // Iterar los resultados
            $array = [];
            while($registro = $resultado->fetch_assoc()) {
                $array[] = static::crearObjeto($registro);
            }
    
            // liberar la memoria
            $resultado->free();
    
            // retornar los resultados
            return $array;
        }
    
        // Crea el objeto en memoria que es igual al de la BD
        protected static function crearObjeto($registro) {
            $objeto = new static;
    
            foreach($registro as $key => $value ) {
                if(property_exists( $objeto, $key  )) {
                    $objeto->$key = $value;
                }
            }
    
            return $objeto;
        }
    
        // Construye los atributos a partir de ColumasDB del Modelo de un objeto
        public function atributos() {
            $atributos = [];
            foreach(static::$columnasDB as $columna) {
                if($columna === 'id') continue;
                $atributos[$columna] = $this->$columna;
            }
            return $atributos;
        }
    
        // Sanitizar los datos antes de guardarlos en la BD
        // Esto es una medida de seguridad para prevenir inyecciones de SQL y asegurarse de que los datos sean seguros antes de ser utilizados en consultas de base de datos.
        public function sanitizarAtributos() {

            $atributos = $this->atributos();
            $sanitizado = [];
            foreach($atributos as $key => $value ) {
                $sanitizado[$key] = self::$db->escape_string($value);
            }
            return $sanitizado;
        }
    
        // Sincroniza los datos de la Vista (mediante POST) con el Objeto actual
        public function sincronizar($args=[]) {
            foreach($args as $key => $value) {
              if(property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
              }
            }
        }
    
        // Registros - CRUD
        public function guardar($id) {
            $resultado = '';
            if(!is_null($id)) {

                // Si no es NULL - Actualizar
                $resultado = $this->actualizar($id);
            } else {

                // Si es NULL - Crear un nuevo registro
                $resultado = $this->crear();
            }
            
            return $resultado;
        }
    
        // Todos los registros
        public static function all() {
            $query = "SELECT * FROM " . static::$tabla;
            $resultado = self::consultarSQL($query);
            return $resultado;
        }
    
        // Busca un registro por su id
        public static function find($nameId, $id) {
            $query = "SELECT * FROM " . static::$tabla  ." WHERE " . $nameId ." = " . $id;
            $resultado = self::consultarSQL($query);
            
            return array_shift( $resultado ) ;
        }

        // Busca un registro por un campo-valor
        public static function where($campo, $valor) {

            $query = "SELECT * FROM " . static::$tabla . " WHERE " . $campo . " = '" . $valor ."'";         
            $resultado = self::consultarSQL($query);
            
            return array_shift( $resultado ) ;
        }

        public static function ultimoID($id) {
            $query = "SELECT * FROM " . static::$tabla . " ORDER BY " . $id . " DESC LIMIT 1";
            $resultado = self::consultarSQL($query);
            
            return array_shift( $resultado ) ;
        }
    
        // Obtener Registros con cierta cantidad
        public static function get($limite) {
            $query = "SELECT * FROM " . static::$tabla . " LIMIT " . $limite;
            $resultado = self::consultarSQL($query);
            return array_shift( $resultado ) ;
        }
    
        // Crear un Registro nuevo
        public function crear() {

            // Sanitizar los datos
            $atributos = $this->sanitizarAtributos();
    
            // Insertar en la base de datos
            $query = " INSERT INTO " . static::$tabla . " ( ";
            $query .= join(', ', array_keys($atributos));
            $query .= " ) VALUES ('"; 
            $query .= join("', '", array_values($atributos));
            $query .= "' ) ";
            
            // Resultado de la consulta
            $resultado = self::$db->query($query);
            
            return [
               'resultado' =>  $resultado,
               'id' => self::$db->insert_id
            ];
        }
    
        // Actualizar el registro
        public function actualizar($id) {
            // Sanitizar los datos
            $atributos = $this->sanitizarAtributos();
    
            // Iterar para ir agregando cada campo de la BD
            $valores = [];
            foreach($atributos as $key => $value) {
                $valores[] = "{$key} = '{$value}'";
            }
    
            // Consulta SQL
            $query = "UPDATE " . static::$tabla ." SET ";
            $query .=  join(', ', $valores );
            $query .= " WHERE " . $this::$idColumn ." = " . self::$db->escape_string($id) . " ";
            $query .= " LIMIT 1 ";
            // Actualizar BD
            $resultado = self::$db->query($query);
            return $resultado;
        }
    
        // Eliminar un Registro por su ID
        public function eliminar($id) {
            $query = "DELETE FROM "  . static::$tabla . " WHERE " . $this::$idColumn ." = " . self::$db->escape_string($id) . " LIMIT 1";
            $resultado = self::$db->query($query);
            return $resultado;
        }
}