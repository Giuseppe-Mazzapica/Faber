<?php
if ( ! class_exists( 'WP_Error' ) ) {

    class WP_Error {

        private $errors = [ ];

        function add( $code = '', $message = '', $arguments = '' ) {
            if ( ! isset( $this->errors[$code] ) ) {
                $this->errors[$code] = [ ];
            }
            $this->errors[$code][] = [ 'code' => $code, 'message' => $message, 'data' => $arguments ];
        }

    }
}

if ( ! function_exists( 'apply_filters' ) ) {

    function apply_filters( $filter, $data ) {
        return $data;
    }

}

if ( ! function_exists( 'do_action' ) ) {

    function do_action( $action, $data = [ ] ) {
        return NULL;
    }

}
