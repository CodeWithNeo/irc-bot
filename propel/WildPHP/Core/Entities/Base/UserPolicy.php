<?php

namespace WildPHP\Core\Entities\Base;

use \Exception;
use \PDO;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use WildPHP\Core\Entities\Policy as ChildPolicy;
use WildPHP\Core\Entities\PolicyQuery as ChildPolicyQuery;
use WildPHP\Core\Entities\UserPolicy as ChildUserPolicy;
use WildPHP\Core\Entities\UserPolicyQuery as ChildUserPolicyQuery;
use WildPHP\Core\Entities\UserPolicyRestriction as ChildUserPolicyRestriction;
use WildPHP\Core\Entities\UserPolicyRestrictionQuery as ChildUserPolicyRestrictionQuery;
use WildPHP\Core\Entities\Map\UserPolicyRestrictionTableMap;
use WildPHP\Core\Entities\Map\UserPolicyTableMap;

/**
 * Base class that represents a row from the 'user_policy' table.
 *
 *
 *
 * @package    propel.generator.WildPHP.Core.Entities.Base
 */
abstract class UserPolicy implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\WildPHP\\Core\\Entities\\Map\\UserPolicyTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the user_irc_account field.
     *
     * @var        string
     */
    protected $user_irc_account;

    /**
     * The value for the policy_name field.
     *
     * @var        string
     */
    protected $policy_name;

    /**
     * @var        ChildPolicy
     */
    protected $aPolicy;

    /**
     * @var        ObjectCollection|ChildUserPolicyRestriction[] Collection to store aggregation of ChildUserPolicyRestriction objects.
     */
    protected $collUserPolicyRestrictions;
    protected $collUserPolicyRestrictionsPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildUserPolicyRestriction[]
     */
    protected $userPolicyRestrictionsScheduledForDeletion = null;

    /**
     * Initializes internal state of WildPHP\Core\Entities\Base\UserPolicy object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>UserPolicy</code> instance.  If
     * <code>obj</code> is an instance of <code>UserPolicy</code>, delegates to
     * <code>equals(UserPolicy)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        if (!$obj instanceof static) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey() || null === $obj->getPrimaryKey()) {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return $this|UserPolicy The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        $cls = new \ReflectionClass($this);
        $propertyNames = [];
        $serializableProperties = array_diff($cls->getProperties(), $cls->getProperties(\ReflectionProperty::IS_STATIC));

        foreach($serializableProperties as $property) {
            $propertyNames[] = $property->getName();
        }

        return $propertyNames;
    }

    /**
     * Get the [user_irc_account] column value.
     *
     * @return string
     */
    public function getUserIrcAccount()
    {
        return $this->user_irc_account;
    }

    /**
     * Get the [policy_name] column value.
     *
     * @return string
     */
    public function getPolicyName()
    {
        return $this->policy_name;
    }

    /**
     * Set the value of [user_irc_account] column.
     *
     * @param string $v new value
     * @return $this|\WildPHP\Core\Entities\UserPolicy The current object (for fluent API support)
     */
    public function setUserIrcAccount($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->user_irc_account !== $v) {
            $this->user_irc_account = $v;
            $this->modifiedColumns[UserPolicyTableMap::COL_USER_IRC_ACCOUNT] = true;
        }

        return $this;
    } // setUserIrcAccount()

    /**
     * Set the value of [policy_name] column.
     *
     * @param string $v new value
     * @return $this|\WildPHP\Core\Entities\UserPolicy The current object (for fluent API support)
     */
    public function setPolicyName($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->policy_name !== $v) {
            $this->policy_name = $v;
            $this->modifiedColumns[UserPolicyTableMap::COL_POLICY_NAME] = true;
        }

        if ($this->aPolicy !== null && $this->aPolicy->getName() !== $v) {
            $this->aPolicy = null;
        }

        return $this;
    } // setPolicyName()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : UserPolicyTableMap::translateFieldName('UserIrcAccount', TableMap::TYPE_PHPNAME, $indexType)];
            $this->user_irc_account = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : UserPolicyTableMap::translateFieldName('PolicyName', TableMap::TYPE_PHPNAME, $indexType)];
            $this->policy_name = (null !== $col) ? (string) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 2; // 2 = UserPolicyTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\WildPHP\\Core\\Entities\\UserPolicy'), 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
        if ($this->aPolicy !== null && $this->policy_name !== $this->aPolicy->getName()) {
            $this->aPolicy = null;
        }
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(UserPolicyTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildUserPolicyQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aPolicy = null;
            $this->collUserPolicyRestrictions = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see UserPolicy::setDeleted()
     * @see UserPolicy::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserPolicyTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildUserPolicyQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $this->setDeleted(true);
            }
        });
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($this->alreadyInSave) {
            return 0;
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(UserPolicyTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $ret = $this->preSave($con);
            $isInsert = $this->isNew();
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                UserPolicyTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }

            return $affectedRows;
        });
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aPolicy !== null) {
                if ($this->aPolicy->isModified() || $this->aPolicy->isNew()) {
                    $affectedRows += $this->aPolicy->save($con);
                }
                $this->setPolicy($this->aPolicy);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    $affectedRows += 1;
                } else {
                    $affectedRows += $this->doUpdate($con);
                }
                $this->resetModified();
            }

            if ($this->userPolicyRestrictionsScheduledForDeletion !== null) {
                if (!$this->userPolicyRestrictionsScheduledForDeletion->isEmpty()) {
                    \WildPHP\Core\Entities\UserPolicyRestrictionQuery::create()
                        ->filterByPrimaryKeys($this->userPolicyRestrictionsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->userPolicyRestrictionsScheduledForDeletion = null;
                }
            }

            if ($this->collUserPolicyRestrictions !== null) {
                foreach ($this->collUserPolicyRestrictions as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;


         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(UserPolicyTableMap::COL_USER_IRC_ACCOUNT)) {
            $modifiedColumns[':p' . $index++]  = 'user_irc_account';
        }
        if ($this->isColumnModified(UserPolicyTableMap::COL_POLICY_NAME)) {
            $modifiedColumns[':p' . $index++]  = 'policy_name';
        }

        $sql = sprintf(
            'INSERT INTO user_policy (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'user_irc_account':
                        $stmt->bindValue($identifier, $this->user_irc_account, PDO::PARAM_STR);
                        break;
                    case 'policy_name':
                        $stmt->bindValue($identifier, $this->policy_name, PDO::PARAM_STR);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserPolicyTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getUserIrcAccount();
                break;
            case 1:
                return $this->getPolicyName();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {

        if (isset($alreadyDumpedObjects['UserPolicy'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['UserPolicy'][$this->hashCode()] = true;
        $keys = UserPolicyTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getUserIrcAccount(),
            $keys[1] => $this->getPolicyName(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aPolicy) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'policy';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'policy';
                        break;
                    default:
                        $key = 'Policy';
                }

                $result[$key] = $this->aPolicy->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collUserPolicyRestrictions) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'userPolicyRestrictions';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'user_policy_channel_restrictions';
                        break;
                    default:
                        $key = 'UserPolicyRestrictions';
                }

                $result[$key] = $this->collUserPolicyRestrictions->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param  string $name
     * @param  mixed  $value field value
     * @param  string $type The type of fieldname the $name is of:
     *                one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                Defaults to TableMap::TYPE_PHPNAME.
     * @return $this|\WildPHP\Core\Entities\UserPolicy
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = UserPolicyTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\WildPHP\Core\Entities\UserPolicy
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setUserIrcAccount($value);
                break;
            case 1:
                $this->setPolicyName($value);
                break;
        } // switch()

        return $this;
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = UserPolicyTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setUserIrcAccount($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setPolicyName($arr[$keys[1]]);
        }
    }

     /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     * @param string $keyType The type of keys the array uses.
     *
     * @return $this|\WildPHP\Core\Entities\UserPolicy The current object, for fluid interface
     */
    public function importFrom($parser, $data, $keyType = TableMap::TYPE_PHPNAME)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), $keyType);

        return $this;
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(UserPolicyTableMap::DATABASE_NAME);

        if ($this->isColumnModified(UserPolicyTableMap::COL_USER_IRC_ACCOUNT)) {
            $criteria->add(UserPolicyTableMap::COL_USER_IRC_ACCOUNT, $this->user_irc_account);
        }
        if ($this->isColumnModified(UserPolicyTableMap::COL_POLICY_NAME)) {
            $criteria->add(UserPolicyTableMap::COL_POLICY_NAME, $this->policy_name);
        }

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @throws LogicException if no primary key is defined
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = ChildUserPolicyQuery::create();
        $criteria->add(UserPolicyTableMap::COL_USER_IRC_ACCOUNT, $this->user_irc_account);
        $criteria->add(UserPolicyTableMap::COL_POLICY_NAME, $this->policy_name);

        return $criteria;
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        $validPk = null !== $this->getUserIrcAccount() &&
            null !== $this->getPolicyName();

        $validPrimaryKeyFKs = 1;
        $primaryKeyFKs = [];

        //relation user_policy_fk_7db62c to table policy
        if ($this->aPolicy && $hash = spl_object_hash($this->aPolicy)) {
            $primaryKeyFKs[] = $hash;
        } else {
            $validPrimaryKeyFKs = false;
        }

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the composite primary key for this object.
     * The array elements will be in same order as specified in XML.
     * @return array
     */
    public function getPrimaryKey()
    {
        $pks = array();
        $pks[0] = $this->getUserIrcAccount();
        $pks[1] = $this->getPolicyName();

        return $pks;
    }

    /**
     * Set the [composite] primary key.
     *
     * @param      array $keys The elements of the composite key (order must match the order in XML file).
     * @return void
     */
    public function setPrimaryKey($keys)
    {
        $this->setUserIrcAccount($keys[0]);
        $this->setPolicyName($keys[1]);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return (null === $this->getUserIrcAccount()) && (null === $this->getPolicyName());
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \WildPHP\Core\Entities\UserPolicy (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setUserIrcAccount($this->getUserIrcAccount());
        $copyObj->setPolicyName($this->getPolicyName());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getUserPolicyRestrictions() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addUserPolicyRestriction($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param  boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return \WildPHP\Core\Entities\UserPolicy Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Declares an association between this object and a ChildPolicy object.
     *
     * @param  ChildPolicy $v
     * @return $this|\WildPHP\Core\Entities\UserPolicy The current object (for fluent API support)
     * @throws PropelException
     */
    public function setPolicy(ChildPolicy $v = null)
    {
        if ($v === null) {
            $this->setPolicyName(NULL);
        } else {
            $this->setPolicyName($v->getName());
        }

        $this->aPolicy = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildPolicy object, it will not be re-added.
        if ($v !== null) {
            $v->addUserPolicy($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildPolicy object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildPolicy The associated ChildPolicy object.
     * @throws PropelException
     */
    public function getPolicy(ConnectionInterface $con = null)
    {
        if ($this->aPolicy === null && (($this->policy_name !== "" && $this->policy_name !== null))) {
            $this->aPolicy = ChildPolicyQuery::create()->findPk($this->policy_name, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aPolicy->addUserPolicies($this);
             */
        }

        return $this->aPolicy;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('UserPolicyRestriction' == $relationName) {
            $this->initUserPolicyRestrictions();
            return;
        }
    }

    /**
     * Clears out the collUserPolicyRestrictions collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addUserPolicyRestrictions()
     */
    public function clearUserPolicyRestrictions()
    {
        $this->collUserPolicyRestrictions = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collUserPolicyRestrictions collection loaded partially.
     */
    public function resetPartialUserPolicyRestrictions($v = true)
    {
        $this->collUserPolicyRestrictionsPartial = $v;
    }

    /**
     * Initializes the collUserPolicyRestrictions collection.
     *
     * By default this just sets the collUserPolicyRestrictions collection to an empty array (like clearcollUserPolicyRestrictions());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initUserPolicyRestrictions($overrideExisting = true)
    {
        if (null !== $this->collUserPolicyRestrictions && !$overrideExisting) {
            return;
        }

        $collectionClassName = UserPolicyRestrictionTableMap::getTableMap()->getCollectionClassName();

        $this->collUserPolicyRestrictions = new $collectionClassName;
        $this->collUserPolicyRestrictions->setModel('\WildPHP\Core\Entities\UserPolicyRestriction');
    }

    /**
     * Gets an array of ChildUserPolicyRestriction objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildUserPolicy is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildUserPolicyRestriction[] List of ChildUserPolicyRestriction objects
     * @throws PropelException
     */
    public function getUserPolicyRestrictions(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collUserPolicyRestrictionsPartial && !$this->isNew();
        if (null === $this->collUserPolicyRestrictions || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collUserPolicyRestrictions) {
                // return empty collection
                $this->initUserPolicyRestrictions();
            } else {
                $collUserPolicyRestrictions = ChildUserPolicyRestrictionQuery::create(null, $criteria)
                    ->filterByUserPolicy($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collUserPolicyRestrictionsPartial && count($collUserPolicyRestrictions)) {
                        $this->initUserPolicyRestrictions(false);

                        foreach ($collUserPolicyRestrictions as $obj) {
                            if (false == $this->collUserPolicyRestrictions->contains($obj)) {
                                $this->collUserPolicyRestrictions->append($obj);
                            }
                        }

                        $this->collUserPolicyRestrictionsPartial = true;
                    }

                    return $collUserPolicyRestrictions;
                }

                if ($partial && $this->collUserPolicyRestrictions) {
                    foreach ($this->collUserPolicyRestrictions as $obj) {
                        if ($obj->isNew()) {
                            $collUserPolicyRestrictions[] = $obj;
                        }
                    }
                }

                $this->collUserPolicyRestrictions = $collUserPolicyRestrictions;
                $this->collUserPolicyRestrictionsPartial = false;
            }
        }

        return $this->collUserPolicyRestrictions;
    }

    /**
     * Sets a collection of ChildUserPolicyRestriction objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $userPolicyRestrictions A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildUserPolicy The current object (for fluent API support)
     */
    public function setUserPolicyRestrictions(Collection $userPolicyRestrictions, ConnectionInterface $con = null)
    {
        /** @var ChildUserPolicyRestriction[] $userPolicyRestrictionsToDelete */
        $userPolicyRestrictionsToDelete = $this->getUserPolicyRestrictions(new Criteria(), $con)->diff($userPolicyRestrictions);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->userPolicyRestrictionsScheduledForDeletion = clone $userPolicyRestrictionsToDelete;

        foreach ($userPolicyRestrictionsToDelete as $userPolicyRestrictionRemoved) {
            $userPolicyRestrictionRemoved->setUserPolicy(null);
        }

        $this->collUserPolicyRestrictions = null;
        foreach ($userPolicyRestrictions as $userPolicyRestriction) {
            $this->addUserPolicyRestriction($userPolicyRestriction);
        }

        $this->collUserPolicyRestrictions = $userPolicyRestrictions;
        $this->collUserPolicyRestrictionsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related UserPolicyRestriction objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related UserPolicyRestriction objects.
     * @throws PropelException
     */
    public function countUserPolicyRestrictions(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collUserPolicyRestrictionsPartial && !$this->isNew();
        if (null === $this->collUserPolicyRestrictions || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collUserPolicyRestrictions) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getUserPolicyRestrictions());
            }

            $query = ChildUserPolicyRestrictionQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByUserPolicy($this)
                ->count($con);
        }

        return count($this->collUserPolicyRestrictions);
    }

    /**
     * Method called to associate a ChildUserPolicyRestriction object to this object
     * through the ChildUserPolicyRestriction foreign key attribute.
     *
     * @param  ChildUserPolicyRestriction $l ChildUserPolicyRestriction
     * @return $this|\WildPHP\Core\Entities\UserPolicy The current object (for fluent API support)
     */
    public function addUserPolicyRestriction(ChildUserPolicyRestriction $l)
    {
        if ($this->collUserPolicyRestrictions === null) {
            $this->initUserPolicyRestrictions();
            $this->collUserPolicyRestrictionsPartial = true;
        }

        if (!$this->collUserPolicyRestrictions->contains($l)) {
            $this->doAddUserPolicyRestriction($l);

            if ($this->userPolicyRestrictionsScheduledForDeletion and $this->userPolicyRestrictionsScheduledForDeletion->contains($l)) {
                $this->userPolicyRestrictionsScheduledForDeletion->remove($this->userPolicyRestrictionsScheduledForDeletion->search($l));
            }
        }

        return $this;
    }

    /**
     * @param ChildUserPolicyRestriction $userPolicyRestriction The ChildUserPolicyRestriction object to add.
     */
    protected function doAddUserPolicyRestriction(ChildUserPolicyRestriction $userPolicyRestriction)
    {
        $this->collUserPolicyRestrictions[]= $userPolicyRestriction;
        $userPolicyRestriction->setUserPolicy($this);
    }

    /**
     * @param  ChildUserPolicyRestriction $userPolicyRestriction The ChildUserPolicyRestriction object to remove.
     * @return $this|ChildUserPolicy The current object (for fluent API support)
     */
    public function removeUserPolicyRestriction(ChildUserPolicyRestriction $userPolicyRestriction)
    {
        if ($this->getUserPolicyRestrictions()->contains($userPolicyRestriction)) {
            $pos = $this->collUserPolicyRestrictions->search($userPolicyRestriction);
            $this->collUserPolicyRestrictions->remove($pos);
            if (null === $this->userPolicyRestrictionsScheduledForDeletion) {
                $this->userPolicyRestrictionsScheduledForDeletion = clone $this->collUserPolicyRestrictions;
                $this->userPolicyRestrictionsScheduledForDeletion->clear();
            }
            $this->userPolicyRestrictionsScheduledForDeletion[]= clone $userPolicyRestriction;
            $userPolicyRestriction->setUserPolicy(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this UserPolicy is new, it will return
     * an empty collection; or if this UserPolicy has previously
     * been saved, it will retrieve related UserPolicyRestrictions from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in UserPolicy.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildUserPolicyRestriction[] List of ChildUserPolicyRestriction objects
     */
    public function getUserPolicyRestrictionsJoinIrcChannel(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildUserPolicyRestrictionQuery::create(null, $criteria);
        $query->joinWith('IrcChannel', $joinBehavior);

        return $this->getUserPolicyRestrictions($query, $con);
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aPolicy) {
            $this->aPolicy->removeUserPolicy($this);
        }
        $this->user_irc_account = null;
        $this->policy_name = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references and back-references to other model objects or collections of model objects.
     *
     * This method is used to reset all php object references (not the actual reference in the database).
     * Necessary for object serialisation.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collUserPolicyRestrictions) {
                foreach ($this->collUserPolicyRestrictions as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collUserPolicyRestrictions = null;
        $this->aPolicy = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(UserPolicyTableMap::DEFAULT_STRING_FORMAT);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preSave')) {
            return parent::preSave($con);
        }
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postSave')) {
            parent::postSave($con);
        }
    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preInsert')) {
            return parent::preInsert($con);
        }
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postInsert')) {
            parent::postInsert($con);
        }
    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preUpdate')) {
            return parent::preUpdate($con);
        }
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postUpdate')) {
            parent::postUpdate($con);
        }
    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        if (is_callable('parent::preDelete')) {
            return parent::preDelete($con);
        }
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {
        if (is_callable('parent::postDelete')) {
            parent::postDelete($con);
        }
    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}