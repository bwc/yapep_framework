<?php
/**
 * This file is part of YAPEPBase.
 *
 * @package      YapepBase
 * @subpackage   Mime
 * @author       Janos Pasztor <j.pasztor@ixolit.com>
 * @copyright    2011 The YAPEP Project All rights reserved.
 * @license      http://www.opensource.org/licenses/bsd-license.php BSD License
 */


namespace YapepBase\Mime;

/**
 * This class contains the most commonly used MIME types as constants
 *
 * @package      YapepBase
 * @subpackage   Mime
 */
class MimeType {
	/** HTML content type */
	const HTML = 'text/html';
	/** XHTML content type */
	const XHTML = 'application/xhtml+xml';
	/** CSS content type */
	const CSS = 'text/css';
	/** Javascript content type */
	const JAVASCRIPT = 'appliation/javascript';
	/** JSON content type */
	const JSON = 'application/json';
	/** XML content type */
	const XML = 'application/xml';
	/** Plain text content type */
	const PLAINTEXT = 'text/plain';
	/** Octet stream content type */
	const OCTET_STREAM = 'application/octet-stream';
}