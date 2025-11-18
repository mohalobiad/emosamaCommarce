<?php
/**
 * XLSX support for the AlSaadrose WooCommerce Arabic plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'TPPLT_XLSX_Reader' ) ) {
    /**
     * Minimal XLSX reader that extracts rows from the first worksheet.
     */
    class TPPLT_XLSX_Reader {
        /**
         * Main spreadsheet namespace.
         */
        const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

        /**
         * Relationship namespace.
         */
        const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        /**
         * Package relationship namespace.
         */
        const NS_PACKAGE_REL = 'http://schemas.openxmlformats.org/package/2006/relationships';

        /**
         * Extract spreadsheet rows.
         *
         * @param string $file        XLSX file path.
         * @param int    $sheet_index Sheet index to parse.
         *
         * @throws Exception When the file cannot be parsed.
         *
         * @return array
         */
        public static function get_rows( $file, $sheet_index = 0 ) {
            if ( ! class_exists( 'ZipArchive' ) ) {
                throw new Exception( __( 'The PHP ZipArchive extension is required to parse XLSX imports.', 'tpplt' ) );
            }

            $zip = new ZipArchive();

            if ( true !== $zip->open( $file ) ) {
                throw new Exception( __( 'Unable to open the uploaded XLSX file.', 'tpplt' ) );
            }

            $workbook_xml = $zip->getFromName( 'xl/workbook.xml' );

            if ( false === $workbook_xml ) {
                $zip->close();
                throw new Exception( __( 'The XLSX file is missing the workbook definition.', 'tpplt' ) );
            }

            $workbook = simplexml_load_string( $workbook_xml );

            if ( ! $workbook ) {
                $zip->close();
                throw new Exception( __( 'The workbook XML could not be parsed.', 'tpplt' ) );
            }

            $workbook->registerXPathNamespace( 'ns', self::NS_MAIN );
            $rels_xml = $zip->getFromName( 'xl/_rels/workbook.xml.rels' );
            $rels     = $rels_xml ? simplexml_load_string( $rels_xml ) : null;

            if ( ! $rels ) {
                $zip->close();
                throw new Exception( __( 'The workbook relationship data is missing from the XLSX file.', 'tpplt' ) );
            }

            $rels->registerXPathNamespace( 'rel', self::NS_PACKAGE_REL );

            $sheets      = $workbook->sheets->sheet ?? array();
            $sheet_nodes = is_array( $sheets ) ? $sheets : iterator_to_array( $sheets, false );

            if ( empty( $sheet_nodes ) ) {
                $zip->close();
                throw new Exception( __( 'The XLSX file does not contain any worksheets.', 'tpplt' ) );
            }

            $sheet_nodes = array_values( $sheet_nodes );

            if ( ! isset( $sheet_nodes[ $sheet_index ] ) ) {
                $zip->close();
                throw new Exception( __( 'The requested sheet does not exist inside the XLSX file.', 'tpplt' ) );
            }

            $sheet_node = $sheet_nodes[ $sheet_index ];
            $sheet_attr = $sheet_node->attributes( 'r', true );
            $sheet_id   = (string) ( $sheet_attr['id'] ?? '' );

            if ( '' === $sheet_id ) {
                $zip->close();
                throw new Exception( __( 'The XLSX worksheet identifier is missing.', 'tpplt' ) );
            }

            $target = '';

            foreach ( $rels->Relationship as $rel ) {
                $attributes = $rel->attributes();

                if ( (string) $attributes['Id'] === $sheet_id ) {
                    $target = (string) $attributes['Target'];
                    break;
                }
            }

            if ( '' === $target ) {
                $zip->close();
                throw new Exception( __( 'The XLSX worksheet target could not be resolved.', 'tpplt' ) );
            }

            $target = 'xl/' . ltrim( $target, '/' );
            $sheet  = $zip->getFromName( $target );

            if ( false === $sheet ) {
                $zip->close();
                throw new Exception( __( 'The XLSX worksheet XML is missing.', 'tpplt' ) );
            }

            $shared_strings = array();
            $shared_xml     = $zip->getFromName( 'xl/sharedStrings.xml' );

            if ( false !== $shared_xml ) {
                $shared = simplexml_load_string( $shared_xml );

                if ( $shared ) {
                    $shared->registerXPathNamespace( 'ns', self::NS_MAIN );
                    foreach ( $shared->si as $string_item ) {
                        $shared_strings[] = self::string_from_shared_item( $string_item );
                    }
                }
            }

            $zip->close();

            $sheet_data = simplexml_load_string( $sheet );

            if ( ! $sheet_data ) {
                throw new Exception( __( 'The worksheet data could not be parsed from the XLSX file.', 'tpplt' ) );
            }

            $sheet_data->registerXPathNamespace( 'ns', self::NS_MAIN );

            $rows_raw     = array();
            $max_columns  = 0;
            $sheet_rows   = $sheet_data->sheetData->row ?? array();
            $sheet_rows   = is_array( $sheet_rows ) ? $sheet_rows : iterator_to_array( $sheet_rows, false );

            foreach ( $sheet_rows as $row ) {
                $cells = array();

                foreach ( $row->c as $cell ) {
                    $reference = strtoupper( (string) ( $cell['r'] ?? '' ) );
                    $index     = self::column_index_from_reference( $reference );
                    $cells[ $index ] = self::cell_value( $cell, $shared_strings );
                }

                if ( ! empty( $cells ) ) {
                    $max_columns = max( $max_columns, max( array_keys( $cells ) ) + 1 );
                }

                $rows_raw[] = $cells;
            }

            $rows = array();

            foreach ( $rows_raw as $cells ) {
                $row = array();

                for ( $column = 0; $column < $max_columns; $column++ ) {
                    $row[] = isset( $cells[ $column ] ) ? $cells[ $column ] : '';
                }

                $rows[] = $row;
            }

            return $rows;
        }

        /**
         * Turn a shared string item into text.
         *
         * @param SimpleXMLElement $item Shared string node.
         *
         * @return string
         */
        protected static function string_from_shared_item( SimpleXMLElement $item ) {
            $text = '';

            if ( isset( $item->t ) ) {
                $text .= (string) $item->t;
            }

            if ( isset( $item->r ) ) {
                foreach ( $item->r as $run ) {
                    if ( isset( $run->t ) ) {
                        $text .= (string) $run->t;
                    }
                }
            }

            return $text;
        }

        /**
         * Parse the value for an individual cell.
         *
         * @param SimpleXMLElement $cell           Cell XML node.
         * @param array            $shared_strings Shared string table.
         *
         * @return string
         */
        protected static function cell_value( SimpleXMLElement $cell, array $shared_strings ) {
            $type = (string) ( $cell['t'] ?? '' );

            switch ( $type ) {
                case 's':
                    $index = isset( $cell->v ) ? (int) $cell->v : null;
                    return isset( $shared_strings[ $index ] ) ? $shared_strings[ $index ] : '';
                case 'b':
                    return isset( $cell->v ) && '1' === (string) $cell->v ? 'TRUE' : 'FALSE';
                case 'inlineStr':
                    return isset( $cell->is->t ) ? (string) $cell->is->t : '';
                default:
                    return isset( $cell->v ) ? (string) $cell->v : '';
            }
        }

        /**
         * Convert a cell reference like A1 or BC42 into a zero-based column index.
         *
         * @param string $reference Cell reference.
         *
         * @return int
         */
        protected static function column_index_from_reference( $reference ) {
            if ( preg_match( '/([A-Z]+)/', $reference, $matches ) ) {
                $letters = $matches[1];
                $length  = strlen( $letters );
                $index   = 0;

                for ( $i = 0; $i < $length; $i++ ) {
                    $index *= 26;
                    $index += ord( $letters[ $i ] ) - 64;
                }

                return max( 0, $index - 1 );
            }

            return 0;
        }
    }
}

if ( ! class_exists( 'TPPLT_XLSX_Converter' ) ) {
    /**
     * Handles converting XLSX files into temporary CSV files.
     */
    class TPPLT_XLSX_Converter {
        /**
         * Check if the provided file is an XLSX spreadsheet.
         *
         * @param string $file File path.
         *
         * @return bool
         */
        public static function is_xlsx_file( $file ) {
            return 'xlsx' === strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
        }

        /**
         * Convert XLSX files to CSV so WooCommerce can keep using its native CSV importer.
         *
         * @param string $file XLSX file path.
         *
         * @throws Exception When conversion fails.
         *
         * @return string
         */
        public static function maybe_convert( $file ) {
            if ( ! self::is_xlsx_file( $file ) ) {
                return $file;
            }

            $sheet_index = (int) apply_filters( 'tpplt/xlsx_import_sheet_index', 0, $file );
            $rows        = TPPLT_XLSX_Reader::get_rows( $file, $sheet_index );

            if ( empty( $rows ) ) {
                throw new Exception( __( 'The uploaded XLSX file does not contain any rows.', 'tpplt' ) );
            }

            $target_dir  = trailingslashit( dirname( $file ) );
            $base_name   = basename( $file, '.xlsx' ) . '-converted.csv';
            $csv_path    = $target_dir . wp_unique_filename( $target_dir, $base_name );
            $handle      = fopen( $csv_path, 'w' );

            if ( ! $handle ) {
                throw new Exception( __( 'Unable to create a temporary CSV file for the XLSX import.', 'tpplt' ) );
            }

            foreach ( $rows as $row ) {
                $safe_row = array_map( array( __CLASS__, 'format_cell_value' ), $row );
                fputcsv( $handle, $safe_row );
            }

            fclose( $handle );

            return $csv_path;
        }

        /**
         * Normalize cell values before writing them back to CSV.
         *
         * @param mixed $value Cell value.
         *
         * @return string
         */
        protected static function format_cell_value( $value ) {
            if ( is_bool( $value ) ) {
                return $value ? 'TRUE' : 'FALSE';
            }

            if ( is_scalar( $value ) ) {
                return (string) $value;
            }

            return '';
        }
    }
}

if ( ! class_exists( 'TPPLT_Product_Importer' ) && class_exists( 'WC_Product_CSV_Importer' ) ) {
    /**
     * Custom importer that transparently converts XLSX files to CSV.
     */
    class TPPLT_Product_Importer extends WC_Product_CSV_Importer {
        /**
         * Track the converted file so it can be cleaned up.
         *
         * @var string
         */
        protected $converted_file = '';

        /**
         * Constructor.
         *
         * @param string $file   File path provided by WooCommerce.
         * @param array  $params Importer parameters.
         *
         * @throws Exception If the XLSX conversion fails.
         */
        public function __construct( $file, $params = array() ) {
            $converted               = TPPLT_XLSX_Converter::maybe_convert( $file );
            $this->converted_file    = ( $converted !== $file ) ? $converted : '';

            parent::__construct( $converted, $params );
        }

        /**
         * Destructor to clean up the temporary CSV file.
         */
        public function __destruct() {
            if ( $this->converted_file && file_exists( $this->converted_file ) ) {
                unlink( $this->converted_file );
            }
        }
    }
}
