<?php
/**
 * This file is part of YAPEPBase.
 *
 * @package      YapepBase
 * @subpackage   Event
 * @author       Zsolt Szeberenyi <szeber@yapep.org>
 * @copyright    2011 The YAPEP Project All rights reserved.
 * @license      http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace YapepBase\Event;

/**
 * EventHandler interface
 *
 * @package    YapepBase
 * @subpackage Event
 */
interface IEventHandler {

	/**
	 * Handles an event
	 *
	 * @param \YapepBase\Event\Event  $event       The dispatched event.
	 */
	public function handleEvent(Event $event);
}