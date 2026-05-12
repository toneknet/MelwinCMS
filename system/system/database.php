<?php

/*
 * The MIT License
 *
 * Copyright 2015 Martin Tonek <martin@tonek.se>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of database
 *
 * @author Martin Tonek <martin@tonek.se>
 */
class database {

    private $db;
    private $input;
    private $showTables = false; // Debug. Skall vara satt till false
    private $return = false; // If return required
    private $output = ""; // the output


    function database($return = false, $dontrun = false) {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
        /*
         * Här skall man kolla ALLA tabeller mot ALLA företag så att de är
         * up2date och existerar!
         * Köra alla rakt av eller anpassa efter kundens paket? Enklast köra
         * alla rakt av.
         *
         *
         */
        // If it should just run, set to true (install use it)
        if ($dontrun) { return;}
        // If it should return output, set to true (install use it)
        if (is_bool($return)) {
            $this->return = $return;
        }

        $this->db = db::getInstance();
        $this->input = new input();
        // Kolla update här
        $sql = $this->input->post('sql');
        if (!empty($sql)) {
            $this->update();
        }
        // Ladda ALLA filer (Filer har alltid m_ som prefix
        $output = $this->generateSqlForm();
        if (!empty($output)) {
            $form = new form();
            $this->output.= $form->form_begin('dbform',array('name'=>'database','class'=>'database'));
            $this->output.= $output;
            $this->output.= $form->form_submit();
            $this->output.= $form->form_end();

            //var_dump($this->output);
/*            print $form->form_begin('dbform',array('name'=>'database','class'=>'database'));
            print $output;
            print $form->form_submit();
            print $form->form_end();

 */
            if ($this->return == true) {
                $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " | RETURN DATA";
                return $this->output;
            } else {
                $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__ . " | PRINT DATA";
                print $this->output;
            }
        } else {
            if ($this->return == true) {
                return true;
            } else {
                setMsg("s", "Alla databaser är uppdaterade!");
            }
        }
    }

    protected function update() {
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
//        return '';
        $sql = $this->input->post('sql');
		if (is_array($sql)) {
			if (is_array($sql)) {
				foreach ($sql as $key) {
					if (isset($_SESSION['sql_commands'][$key])) {
                        $this->db->o_query($_SESSION['sql_commands'][$key]);
					} // if
				} // foreach
			} // if
			$_SESSION['sql_commands'] = array();
		} // if
        setMsg('s', "Ändringar har genomförts!");
    }

    protected function generateSqlForm()
	{
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
		$count = 0;
		$return = '';
		$sql_command = $this->compileCommands();

        if (!count($sql_command))
		{
			return '';
		}

		$_SESSION['sql_commands'] = array();
		//$this->loadLanguageFile('tl_install');
//        include('tl_install.php');
		$arrOperations = array
        (
			'CREATE'        => $GLOBALS['LANG']['install']['CREATE'],
			'ALTER_ADD'     => $GLOBALS['LANG']['install']['ALTER_ADD'],
			'ALTER_CHANGE'  => $GLOBALS['LANG']['install']['ALTER_CHANGE'],
			'ALTER_DROP'    => $GLOBALS['LANG']['install']['ALTER_DROP'],
			'DROP'          => $GLOBALS['LANG']['install']['DROP']
		);

        $return .= "
<script type=\"text/javascript\">
    $(document).ready(function() {
        $('.checkall').change(function () {
            var check_class = ($(this).attr('id'));
            $('.'+check_class).prop('checked', $(this).prop('checked'));
        });
    });
</script>



            ";

		foreach ($arrOperations as $command=>$label)
		{
			if (is_array($sql_command[$command]))
			{
				// Headline
				$return .= '
    <tr>
      <td colspan="2" class="tl_col_0"><strong>'.$label.'</strong></td>
    </tr>';

				// Check all
				$return .= '
    <tr>
<!--      <td class="tl_col_1"><input type="checkbox" id="check_all_' . $count . '" class="tl_checkbox" onclick="Backend.toggleCheckboxElements(this, \'' . strtolower($command) . '\')" /></td>-->
      <td class="tl_col_1"><input type="checkbox" id="' . strtolower($command) . '" name="' . strtolower($command) . '" class="tl_checkbox checkall" /></td>
      <td class="tl_col_2"><label for="' . strtolower($command) . '" style="color:#a6a6a6;"><em>Select all</em></label></td>
    </tr>';

				// Fields
				foreach ($sql_command[$command] as $vv)
				{
					$key = md5($vv);
					$_SESSION['sql_commands'][$key] = $vv;

					$return .= '
    <tr>
      <td class="tl_col_1"><input type="checkbox" name="sql[]" id="sql_'.$count.'" class="tl_checkbox ' . strtolower($command) . '" value="'.$key.'"'.((stristr($command, 'DROP') === false) ? ' checked="checked"' : '').' /></td>
      <td class="tl_col_2"><pre><label for="sql_'.$count++.'">'.$vv.'</label></pre></td>
    </tr>';
				}
			}
		}

		return '
  <table cellspacing="0" cellpadding="0" id="sql_table" summary="Database modifications">'.$return.'
  </table>' . "\n";
	}

    /**
	 * Compile a command array for each necessary database modification
	 * @return array
	 */
	protected function compileCommands()
	{
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
		$drop = array();
		$create = array();
		$return = array();

		$sql_current = $this->getFromDb();

        if ($this->showTables) {
            // Bygger temporär CREATE sats här
            $output="";
            foreach ($sql_current as $table => $data) {
                $output.="CREATE TABLE `{$table}` (\n";
                $output.= implode (",\n",$data['TABLE_FIELDS']);
                $output.=",\n";
                $output.= implode (",\n",$data['TABLE_CREATE_DEFINITIONS']);
                $output.="\n";
                $output.=") ENGINE=MyISAM DEFAULT CHARSET=utf8;\n\n";
            }

//            print "<pre>";
//            print $output;
//            print "</pre>";
        }

        $sql_target = $this->getFromFile();

        /*
         * Här skall ALLA gås igenom för att kolla vilka prefix som är ok samt
         * lägga till för de som inte är ok än
         */
        $arrCompanies = $this->db->o_get_all('company','SELECT dbprefix,namn,id FROM [table]','dbprefix');
        $arrPublicTables = explode(",", $GLOBALS['CONFIG']['public_tables']);
        foreach ($sql_target as $table => $value) {
            $strTmp = str_replace('m_', '', $table);
            if (!in_array($strTmp, $arrPublicTables)) {
                if (is_array($arrCompanies)) {
                    foreach ($arrCompanies as $key => $dummy) {
    // Måste fixa så att man bara byter tillfälligt på denna och inte direkt!
                        $xvalue = $value;
    //                     print "INNAN: (".$dummy['id'].") " . $xvalue['TABLE_FIELDS']['companyid'] . "<br>";
                        $xvalue['TABLE_FIELDS']['companyid']=str_replace("NULL default '0'","NULL default '". $dummy['id'] . "'",$xvalue['TABLE_FIELDS']['companyid']);
    //                     print "EFTER: (".$dummy['id'].") " . $xvalue['TABLE_FIELDS']['companyid'] . "<br>";
                        $sql_target[$key.$strTmp] = $xvalue;
                    }
                }
                unset($sql_target[$table]);
            }
        }

        // Create tables
		foreach (array_diff(array_keys($sql_target), array_keys($sql_current)) as $table)
		{
			$return['CREATE'][] = "CREATE TABLE `" . $table . "` (\n  " . implode(",\n  ", $sql_target[$table]['TABLE_FIELDS']) . (count($sql_target[$table]['TABLE_CREATE_DEFINITIONS']) ? ',' : '') . "\n  " . implode(",\n  ", $sql_target[$table]['TABLE_CREATE_DEFINITIONS']) . "\n)".$sql_target[$table]['TABLE_OPTIONS'].';';
			$create[] = $table;
		}

		// Add or change fields
		foreach ($sql_target as $k=>$v)
		{
			if (in_array($k, $create))
			{
				continue;
			}

			// Fields
			if (is_array($v['TABLE_FIELDS']))
			{
				foreach ($v['TABLE_FIELDS'] as $kk=>$vv)
				{
					if (!isset($sql_current[$k]['TABLE_FIELDS'][$kk]))
					{
						$return['ALTER_ADD'][] = 'ALTER TABLE `'.$k.'` ADD '.$vv.';';
					}
					elseif ($sql_current[$k]['TABLE_FIELDS'][$kk] != $vv)
					{
						$return['ALTER_CHANGE'][] = 'ALTER TABLE `'.$k.'` CHANGE `'.$kk.'` '.$vv.';';
					}
				}
			}

			// Create definitions
			if (is_array($v['TABLE_CREATE_DEFINITIONS']))
			{
				foreach ($v['TABLE_CREATE_DEFINITIONS'] as $kk=>$vv)
				{
					if (!isset($sql_current[$k]['TABLE_CREATE_DEFINITIONS'][$kk]))
					{
						$return['ALTER_ADD'][] = 'ALTER TABLE `'.$k.'` ADD '.$vv.';';
					}
					elseif ($sql_current[$k]['TABLE_CREATE_DEFINITIONS'][$kk] != str_replace('FULLTEXT ', '', $vv))
					{
						$return['ALTER_CHANGE'][] = 'ALTER TABLE `'.$k.'` DROP INDEX `'.$kk.'`, ADD '.$vv.';';
					}
				}
			}

			// Move auto_increment fields to the end of the array
			if (is_array($return['ALTER_ADD']))
			{
				foreach (preg_grep('/auto_increment/i', $return['ALTER_ADD']) as $kk=>$vv)
				{
					unset($return['ALTER_ADD'][$kk]);
					$return['ALTER_ADD'][$kk] = $vv;
				}
			}

			if (is_array($return['ALTER_CHANGE']))
			{
				foreach (preg_grep('/auto_increment/i', $return['ALTER_CHANGE']) as $kk=>$vv)
				{
					unset($return['ALTER_CHANGE'][$kk]);
					$return['ALTER_CHANGE'][$kk] = $vv;
				}
			}
		}

		// Drop tables
    // Kommenterar bort droppa tables så länge för att undvika trassel
		foreach (array_diff(array_keys($sql_current), array_keys($sql_target)) as $table)
		{
			$return['DROP'][] = 'DROP TABLE `'.$table.'`;';
			$drop[] = $table;
		}

		// Drop fields
		foreach ($sql_current as $k=>$v)
		{
			if (!in_array($k, $drop))
			{
				// Fields
				if (is_array($v['TABLE_FIELDS']))
				{
					foreach ($v['TABLE_FIELDS'] as $kk=>$vv)
					{
						if (!isset($sql_target[$k]['TABLE_FIELDS'][$kk]))
						{
							$return['ALTER_DROP'][] = 'ALTER TABLE `'.$k.'` DROP `'.$kk.'`;';
						}
					}
				}

				// Create definitions
				if (is_array($v['TABLE_CREATE_DEFINITIONS']))
				{
					foreach ($v['TABLE_CREATE_DEFINITIONS'] as $kk=>$vv)
					{
						if (!isset($sql_target[$k]['TABLE_CREATE_DEFINITIONS'][$kk]))
						{
							$return['ALTER_DROP'][] = 'ALTER TABLE `'.$k.'` DROP INDEX `'.$kk.'`;';
						}
					}
				}
			}
		}

		return $return;
	}


    /**
	 * Compile a table array from the database and return it
	 * @return array
	 */
	protected function getFromDB()
	{
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
//		$tables = preg_grep('/^m_/i', $this->db->o_list_tables());
        // Vi skall ju bearbeta dessa igen sedan
		$tables = $this->db->o_list_tables();

		if (!count($tables))
		{
			return array();
		}

		$return = array();

		foreach ($tables as $table)
		{
			$fields = $this->db->o_list_fields($table);

            foreach ($fields as $field)
			{
				$name = $field['name'];
				$field['name'] = '`'.$field['name'].'`';

				// Field type
				if (strlen($field['length']))
				{
					$field['type'] .= '(' . $field['length'] . (strlen($field['precision']) ? ',' . $field['precision'] : '') . ')';

					unset($field['length']);
					unset($field['precision']);
				}

				// Default values
				if (in_array(strtolower($field['type']), array('text', 'tinytext', 'mediumtext', 'longtext', 'blob', 'tinyblob', 'mediumblob', 'longblob')) || stristr($field['extra'], 'auto_increment'))
				{
					unset($field['default']);
				}

				elseif (is_null($field['default']) || strtolower($field['default']) == 'null')
				{
					$field['default'] = "default NULL";
				}

				else
				{
					$field['default'] = "default '" . $field['default'] . "'";
				}

				// Indices
				if (strlen($field['index']))
				{
					switch ($field['index'])
					{
						case 'PRIMARY':
							$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'PRIMARY KEY  (`'.$name.'`)';
							break;

						case 'UNIQUE':
							$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'UNIQUE KEY `'.$name.'` (`'.$name.'`)';
							break;

						case 'FULLTEXT':
							$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'FULLTEXT KEY `'.$name.'` (`'.$name.'`)';
							break;

						default:
							$return[$table]['TABLE_CREATE_DEFINITIONS'][$name] = 'KEY `'.$name.'` (`'.$name.'`)';
							break;
					}

					unset($field['index']);
				}

				$return[$table]['TABLE_FIELDS'][$name] = trim(implode(' ', $field));
			}
		}

		return $return;
	}


    /**
	 * Compile a table array from all SQL files and return it
	 * @return array
	 */
	protected function getFromFile()
	{
        $GLOBALS['DEBUG'][] = __CLASS__ . " :: " . __FUNCTION__;
		$return = array();

        // Hur ladda sql från lang biblioteket?

		// Get all SQL files
		foreach (scan(MODPATH) as $strModule)
		{
//            print $strModule;

			if (strncmp($strModule, '.', 1) === 0 || strncmp($strModule, '__', 2) === 0 ) //|| strncmp($strModule, '_', 1) === 0 )
			{
				continue;
			}

            if (substr($strModule, 0,1) == "_")
            {
                continue;
            }

			$strFile = sprintf('%s%s/config/database.sql', MODPATH, $strModule);
            print $strFile."<br>";

			if (!file_exists($strFile))
			{
				continue;
			}

			$data = file($strFile);

            // Call new functrion
            $return = $this->parseFile2Array($data,$return);
		}

        // Språkfiler här
        foreach (scan(SYSPATH ."lang/") as $strModule)
		{
            if (strlen($strModule)>4) {
                if (substr($strModule, -4, 4) !== ".sql") {
                    continue;
                }
            } else {
                continue;
            }

            if (strncmp($strModule, '.', 1) === 0 || strncmp($strModule, '__', 2) === 0 ) //|| strncmp($strModule, '_', 1) === 0 )
			{
				continue;
			}

			$strFile = SYSPATH ."lang/" . $strModule; //sprintf('%s%s/config/database.sql', MODPATH, $strModule);
//            print $strFile."<br>";

			if (!file_exists($strFile))
			{
				continue;
			}

            $data = file($strFile);

            // Call new functrion
            $return = $this->parseFile2Array($data,$return);
        }

        $GLOBALS['DEBUG'][] = $return;
		return $return;
	}

    private function parseFile2Array($data, $return = array())
    {

        foreach ($data as $k=>$v)
        {
            $key_name = array();
            $subpatterns = array();

            // Unset comments and empty lines
            if (preg_match('/^[#-]+/i', $v) || !strlen(trim($v)))
            {
                unset($data[$k]);
                continue;
            }

            // Store table names
            if (preg_match('/^CREATE TABLE `([^`]+)`/i', $v, $subpatterns))
            {
                $table = $subpatterns[1];
            }

            // Get table options
            elseif (strlen($table) && preg_match('/^\)([^;]+);/i', $v, $subpatterns))
            {
                $return[$table]['TABLE_OPTIONS'] = $subpatterns[1];
                $table = '';
            }

            // Add fields
            elseif (strlen($table))
            {
                preg_match('/^[^`]*`([^`]+)`/i', trim($v), $key_name);

                $first = preg_replace('/\s[^\n\r]+/i', '', $key_name[0]);
                $key = $key_name[1];

                // Create definitions
                if (in_array($first, array('KEY', 'PRIMARY', 'PRIMARY KEY', 'FOREIGN', 'FOREIGN KEY', 'INDEX', 'UNIQUE', 'FULLTEXT', 'CHECK')))
                {
                    $return[$table]['TABLE_CREATE_DEFINITIONS'][$key] = preg_replace('/,$/i', '', trim($v));
                }

                else
                {
                    $return[$table]['TABLE_FIELDS'][$key] = preg_replace('/,$/i', '', trim($v));
                }
            }
        }
        return $return;

    }

}
