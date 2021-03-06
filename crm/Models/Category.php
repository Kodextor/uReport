<?php
/**
 * @copyright 2011-2015 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
namespace Application\Models;
use Blossom\Classes\ActiveRecord;
use Blossom\Classes\Database;

class Category extends ActiveRecord
{
	protected $tablename = 'categories';

	protected $department;
	protected $categoryGroup;

	private $displayPermissionLevelHasChanged = false;
	private $slaDaysHasChanged = false;

	/**
	 * Populates the object with data
	 *
	 * Passing in an associative array of data will populate this object without
	 * hitting the database.
	 *
	 * Passing in a scalar will load the data from the database.
	 * This will load all fields in the table as properties of this class.
	 * You may want to replace this with, or add your own extra, custom loading
	 *
	 * @param int|array $id
	 */
	public function __construct($id=null)
	{
		if ($id) {
			if (is_array($id)) {
				$this->exchangeArray($id);
			}
			else {
				$sql = ActiveRecord::isId($id)
					? 'select * from categories where id=?'
					: 'select * from categories where name=?';

				$zend_db = Database::getConnection();
				$result = $zend_db->createStatement($sql)->execute([$id]);
				if (count($result)) {
					$this->exchangeArray($result->current());
				}
				else {
					throw new \Exception('categories/unknownCategory');
				}
			}
		}
		else {
			// This is where the code goes to generate a new, empty instance.
			// Set any default values for properties that need it here
		}
	}

    /**
     * When repopulating with fresh data, make sure to set default
     * values on all object properties.
     *
     * @Override
     * @param array $data
     */
    public function exchangeArray($data)
    {
        parent::exchangeArray($data);

        $this->department    = null;
        $this->categoryGroup = null;
        $this->displayPermissionLevelHasChanged = false;
    }

	/**
	 * Throws an exception if anything's wrong
	 * @throws Exception $e
	 */
	public function validate()
	{
		if (!$this->getName())             { throw new \Exception('categories/missingName');  }
		if (!$this->getCategoryGroup_id()) { throw new \Exception('categories/missingGroup'); }
		if (!$this->getDepartment_id())    { throw new \Exception('categories/missingDepartment'); }
	}

	public function save() {
		$this->setLastModified(date(DATE_FORMAT));
		parent::save();

		if ($this->displayPermissionLevelHasChanged || $this->slaDaysHasChanged) {
			// Spawn a background process to reindex the search engine
			$cmd = PHP.' '.APPLICATION_HOME.'/scripts/workers/indexCategory.php '.SITE_HOME.' '.$this->getId();
			if ($this->slaDaysHasChanged) { $cmd .= ' open'; }

			shell_exec("nohup $cmd > /dev/null 2>&1 &");
		}
	}

	//----------------------------------------------------------------
	// Getters and Setters
	//----------------------------------------------------------------
	public function __toString()                { return parent::get('name');                   }
	public function getId()                     { return parent::get('id');                     }
	public function getName()                   { return parent::get('name');                   }
	public function getDepartment_id()          { return parent::get('department_id');          }
	public function getCategoryGroup_id()       { return parent::get('categoryGroup_id');       }
	public function getDescription()            { return parent::get('description');            }
	public function getPostingPermissionLevel() { return parent::get('postingPermissionLevel'); }
	public function getDisplayPermissionLevel() { return parent::get('displayPermissionLevel'); }
	public function getSlaDays()                { return parent::get('slaDays');                }
	public function getDepartment()    { return parent::getForeignKeyObject(__namespace__.'\Department',    'department_id');    }
	public function getCategoryGroup() { return parent::getForeignKeyObject(__namespace__.'\CategoryGroup', 'categoryGroup_id'); }
	public function getLastModified($format=null, \DateTimeZone $timezone=null) { return parent::getDateData('lastModified', $format, $timezone); }

	public function setName                  ($s) { parent::set('name',                  $s); }
	public function setDescription           ($s) { parent::set('description',           $s); }
	public function setPostingPermissionLevel($s) { parent::set('postingPermissionLevel',$s); }
	public function setDepartment_id   ($id)           { parent::setForeignKeyField( __namespace__.'\Department',    'department_id',    $id); }
	public function setCategoryGroup_id($id)           { parent::setForeignKeyField( __namespace__.'\CategoryGroup', 'categoryGroup_id', $id); }
	public function setDepartment   (Department    $o) { parent::setForeignKeyObject(__namespace__.'\Department',    'department_id',    $o);  }
	public function setCategoryGroup(CategoryGroup $o) { parent::setForeignKeyObject(__namespace__.'\CategoryGroup', 'categoryGroup_id', $o);  }
	public function setLastModified($d) { parent::setDateData('lastModified', $d); }

    public function setDisplayPermissionLevel($s)
    {
        if ($this->getId()) {
            $s = trim($s);
            if (   $this->getDisplayPermissionLevel()
                && $this->getDisplayPermissionLevel() != $s) {
                $this->displayPermissionLevelHasChanged = true;
            }
        }
        parent::set('displayPermissionLevel',$s);
    }

    public function setSlaDays($i)
    {
        $i = (int)$i;

        if ($this->getId()) {
            if ($this->getSlaDays() != $i) {
                $this->slaDaysHasChanged = true;
            }
        }
        parent::set('slaDays', $i);
    }

	/**
	 * @param array $post
	 */
	public function handleUpdate($post)
	{
		$this->setName                  ($post['name']);
		$this->setDescription           ($post['description']);
		$this->setDepartment_id         ($post['department_id']);
		$this->setCategoryGroup_id      ($post['categoryGroup_id']);
		$this->setPostingPermissionLevel($post['postingPermissionLevel']);
		$this->setDisplayPermissionLevel($post['displayPermissionLevel']);
		$this->setCustomFields          ($post['custom_fields']);
		$this->setSlaDays               ($post['slaDays']);
	}
	//----------------------------------------------------------------
	// Custom Functions
	//----------------------------------------------------------------
	/**
	 * Returns a PHP array representing the description of custom fields
	 *
	 * The category holds the description of the custom fields desired
	 * $customFields = array(
	 *		array('name'=>'','type'=>'','label'=>'','values'=>array())
	 * )
	 * Name and Label are required.
	 * Anything without a type will be rendered as type='text'
	 * If type is select, radio, or checkbox, you must provide values
	 *		for the user to choose from
	 *
	 * @return array
	 */
	public function getCustomFields()
	{
		return json_decode(parent::get('customFields'));
	}

	/**
	 * Loads a valid JSON string describing the custom fields
	 *
	 * The category holds the description of the custom fields desired
	 * $json = [
	 *		{'name':'','type':'','label':'','values':['',''])
	 * ]
	 * Name and Label are required.
	 * Anything without a type will be rendered as type='text'
	 * If type is select, radio, or checkbox, you must provide values
	 *		for the user to choose from
	 *
	 * @param string $json
	 */
	public function setCustomFields($json=null)
	{
		$json = trim($json);
		$customFields = '';
		if ($json) {
			$customFields = json_decode($json);
			if (is_array($customFields)) {
				$this->data['customFields'] = $json;
			}
			else {
				$message = json_last_error();
				if ($message != JSON_ERROR_NONE) {
					throw new JSONException($message);
				}
			}
		}
		else {
			unset($this->data['customFields']);
		}
	}

	/**
	 * @param Person $person
	 * @return bool
	 */
	public function allowsDisplay($person)
	{
		if (!$person instanceof Person) {
			return $this->getDisplayPermissionLevel()=='anonymous';
		}
		elseif ($person->getRole()!='Staff' && $person->getRole()!='Administrator') {
			return in_array(
				$this->getDisplayPermissionLevel(),
				array('public','anonymous')
			);
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function allowsPosting($person)
	{
		if (!$person instanceof Person) {
			return $this->getPostingPermissionLevel()=='anonymous';
		}
		elseif ($person->getRole()!='Staff' && $person->getRole()!='Administrator') {
			return in_array(
				$this->getPostingPermissionLevel(),
				array('public','anonymous')
			);
		}
		return true;
	}

	/**
	 * Returns the most recent lastModified date from all categories
	 *
	 * @param string $format
	 * @param DateTimeZone $timezone
	 * @return string
	 */
	public static function getGlobalLastModifiedDate($format=null, \DateTimeZone $timezone=null)
	{
		$zend_db = Database::getConnection();
		$result = $zend_db->query('select max(lastModified) as lastModified from categories')->execute();
		$row = $result->current();

		if ($format) {
			$date = new \DateTime($row['lastModified']);
			if ($timezone) { $date->setTimezone($timezone); }
			return $date->format($format);
		}
		else {
			return $row['lastModified'];
		}
	}
}

class JSONException extends \Exception
{
	public function __construct($message, $code=0, \Exception $previous=null)
	{
		switch ($message) {
			case JSON_ERROR_NONE:
				$this->message = 'No errors';
			break;
			case JSON_ERROR_DEPTH:
				$this->message = 'Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				$this->message = 'Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				$this->message = 'Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				$this->message = 'Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				$this->message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				$this->message = 'Unknown JSON error';
			break;
		}
	}
}
