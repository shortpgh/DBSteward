<?php
/**
 * Diffs functions.
 *
 * @package DBSteward
 * @subpackage mysql5
 * @license http://www.opensource.org/licenses/bsd-license.php Simplified BSD License
 * @author Austin Hyde <austin109@gmail.com>
 */

class mysql5_diff_functions extends sql99_diff_functions {
  /**
   * Outputs DDL for differences in functions
   *
   * @param $ofs1       stage1 output pointer
   * @param $ofs3       stage3 output pointer
   * @param $old_schema original schema
   * @param $new_schema new schema
   */
  public static function diff_functions($ofs1, $ofs3, $old_schema, $new_schema) {
    // drop functions that no longer exist in stage 3
    if ($old_schema != null) {
      foreach(dbx::get_functions($old_schema) as $old_function) {
        if (!mysql5_schema::contains_function($new_schema, mysql5_function::get_declaration($new_schema, $old_function))) {
          $ofs3->write(mysql5_function::get_drop_sql($old_schema, $old_function) . "\n");
        }
      }
    }

    // Add new functions and replace modified functions
    foreach(dbx::get_functions($new_schema) AS $new_function) {
      $old_function = null;
      if ($old_schema != null) {
        $old_function = dbx::get_function($old_schema, $new_function['name'], mysql5_function::get_declaration($new_schema, $new_function));
      }

      if ( $old_function == null || !mysql5_function::equals($new_schema, $new_function, $old_function, mysql5_diff::$ignore_function_whitespace) ) {
        $ofs1->write(mysql5_function::get_creation_sql($new_schema, $new_function) . "\n");
      }
      else if ( isset($new_function['forceRedefine']) && strcasecmp($new_function['forceRedefine'], 'true') == 0 ) {
        $ofs1->write("-- DBSteward insists on function recreation: {$new_schema['name']}.{$new_function['name']} has forceRedefine set to true\n");
        $ofs1->write(mysql5_function::get_creation_sql($new_schema, $new_function) . "\n");
      }
      else if (mysql5_schema::contains_type($new_schema, $new_function['returns'])
          && mysql5_schema::contains_type($old_schema, $new_function['returns'])
          && ! mysql5_type::equals(dbx::get_type($old_schema, $new_function['returns']),
                                   dbx::get_type($new_schema, $new_function['returns'])) ) {
        $ofs1->write("-- Force function re-creation {$new_function['name']} for type: {$new_function['returns']}\n");
        $ofs1->write(mysql5_function::get_creation_sql($new_schema, $new_function) . "\n");
      }
    }
  }
}