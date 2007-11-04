<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'propel/engine/platform/Platform.php';
include_once 'propel/engine/database/model/Domain.php';
include_once 'propel/engine/database/model/PropelTypes.php';

/**
 * Default implementation for the Platform interface.
 *
 * @author     Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version    $Revision: 745 $
 * @package    propel.engine.platform
 */
class DefaultPlatform implements Platform {

	private $schemaDomainMap;

	/**
	 * @var        PDO Database connection.
	 */
	private $con;

	/**
	 * Default constructor.
	 * @param      PDO $con Optional database connection to use in this platform.
	 */
	public function __construct(PDO $con = null)
	{
		$this->initialize();
	}

	/**
	 * Set the database connection to use for this Platform class.
	 * @param      PDO $con Database connection to use in this platform.
	 */
	public function setConnection(PDO $con = null)
	{
		$this->con = $con;
	}

	/**
	 * Returns the database connection to use for this Platform class.
	 * @return     PDO The database connection or NULL if none has been set.
	 */
	public function getConnection()
	{
		return $this->con;
	}

	protected function initialize()
	{
		$this->schemaDomainMap = array();
		foreach (PropelTypes::getPropelTypes() as $type) {
			$this->schemaDomainMap[$type] = new Domain($type);
		}
		$this->schemaDomainMap[PropelTypes::BU_DATE] = new Domain("DATE");
		$this->schemaDomainMap[PropelTypes::BU_TIMESTAMP] = new Domain("TIMESTAMP");
		$this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain("INTEGER");
	}

	protected function setSchemaDomainMapping(Domain $domain)
	{
		$this->schemaDomainMap[$domain->getType()] = $domain;
	}

	/**
	 * Returns the short name of the database type that this platform represents.
	 * For example MysqlPlatform->getDatabaseType() returns 'mysql'.
	 * @return     string
	 */
	public function getDatabaseType()
	{
		$clazz = get_class($this);
		$pos = strpos($clazz, 'Platform');
		return strtolower(substr($clazz,0,$pos));
	}

	/**
	 * @see        Platform::getMaxColumnNameLength()
	 */
	public function getMaxColumnNameLength()
	{
		return 64;
	}

	/**
	 * @see        Platform::getNativeIdMethod()
	 */
	public function getNativeIdMethod()
	{
		return Platform::IDENTITY;
	}

	/**
	 * @see        Platform::getDomainForType()
	 */
	public function getDomainForType($propelType)
	{
		if (!isset($this->schemaDomainMap[$propelType])) {
			throw new EngineException("Cannot map unknown Propel type " . var_export($propelType, true) . " to native database type.");
		}
		return $this->schemaDomainMap[$propelType];
	}

	/**
	 * @return     Only produces a SQL fragment if null values are
	 * disallowed.
	 * @see        Platform::getNullString(boolean)
	 */
	public function getNullString($notNull)
	{
		// TODO: Check whether this is true for all DBs.  Also verify
		// the old Sybase templates.
		return ($notNull ? "NOT NULL" : "");
	}

	/**
	 * @see        Platform::getAutoIncrement()
	 */
	public function getAutoIncrement()
	{
		return "IDENTITY";
	}

	/**
	 * @see        Platform::hasScale(String)
	 * TODO collect info for all platforms
	 */
	public function hasScale($sqlType)
	{
		return true;
	}

	/**
	 * @see        Platform::hasSize(String)
	 * TODO collect info for all platforms
	 */
	public function hasSize($sqlType)
	{
		return true;
	}

	/**
	 * @see        Platform::quote()
	 */
	public function quote($text)
	{
		if ($this->getConnection()) {
			return $this->getConnection()->quote($text);
		} else {
			return "'" . $this->disconnectedEscapeText($text) . "'";
		}
	}

	/**
	 * Method to escape text when no connection has been set.
	 *
	 * The subclasses can implement this using string replacement functions
	 * or native DB methods.
	 *
	 * @param      string $text Text that needs to be escaped.
	 * @return     string
	 */
	protected function disconnectedEscapeText($text)
	{
		return str_replace("'", "''", $text);
	}

	/**
	 * @see        Platform::quoteIdentifier()
	 */
	public function quoteIdentifier($text)
	{
		return '"' . $text . '"';
	}

	/**
	 * @see        Platform::supportsNativeDeleteTrigger()
	 */
	public function supportsNativeDeleteTrigger()
	{
		return false;
	}

	/**
	 * Whether the underlying PDO driver for this platform returns BLOB columns as streams (instead of strings).
	 * @return     boolean
	 */
	public function hasStreamBlobImpl()
	{
		return false;
	}

	/**
	 * @see        Platform::getBooleanString()
	 */
	public function getBooleanString($b)
	{
		$b = ($b === true || strtolower($b) === 'true' || $b === 1 || $b === '1' || strtolower($b) === 'y' || strtolower($b) === 'yes');
		return ($b ? '1' : '0');
	}
	
	/**
	 * Gets the preferred timestamp formatter for setting date/time values.
	 * @return string
	 */
	public function getTimestampFormatter()
	{
		return DateTime::ISO8601;
	}
}
