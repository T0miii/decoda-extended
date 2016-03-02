<?php
/**
 * @copyright   2006-2014, Miles Johnson - http://milesj.me
 * @license     https://github.com/milesj/decoda/blob/master/license.md
 * @link        http://milesj.me/code/php/decoda
 */

namespace Decoda\Filter;

use Decoda\Decoda;

/**
 * Provides tags for emails. Will obfuscate emails against bots.
 */
class EmailFilter extends AbstractFilter {

    /**
     * Configuration.
     *
     * @type array
     */
    protected $_config = array(
        'encrypt' => true
    );

    /**
     * Supported tags.
     *
     * @type array
     */
    protected $_tags = array(
        'email' => array(
            'htmlTag' => 'a',
            'displayType' => Decoda::TYPE_INLINE,
            'allowedTypes' => Decoda::TYPE_NONE,
            'escapeAttributes' => false,
            'attributes' => array(
                'default' => true,
            	'subject' => AbstractFilter::WILDCARD,
            	'body' => AbstractFilter::WILDCARD,
            	'cc' => AbstractFilter::WILDCARD,
            	'bcc' => AbstractFilter::WILDCARD,
            	'class' => AbstractFilter::WILDCARD,
            )
        ),
        'mail' => array(
            'aliasFor' => 'email'
        )
    );

    /**
     * Encrypt the email before parsing it within tags.
     *
     * @param array $tag
     * @param string $content
     * @return string
     */
    public function parse(array $tag, $content) {
        if (empty($tag['attributes']['default'])) {
            $email = $content;
            $default = false;
        } else {
            $email = $tag['attributes']['default'];
            $default = true;
        }

        // Return an invalid email
        /*
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $content;
        }
		*/
        $encrypted = $this->_encrypt($email);

        $tag['attributes']['href'] = 'mailto:' . $encrypted;

        if (count(array_intersect_key(array_flip($email_attr), $tag['attributes']))>0) {
        	$tag['attributes']['href'] .= '?';
        }

        foreach($email_attr as $attr) {
        	if (isset($tag['attributes'][$attr])) {
        		$value_encrypted = $this->_encrypt($tag['attributes'][$attr]);
        		$tag['attributes']['href'] .= $attr.'='.$value_encrypted;
        		unset($tag['attributes'][$attr]);
        		if (count(array_intersect_key(array_flip($email_attr), $tag['attributes']))>0) {
        			$tag['attributes']['href'] .= '&';
        		}
        	}
        }

        if ($this->getParser()->getConfig('shorthandLinks')) {
            $tag['content'] = $this->message('mail');

            return '[' . parent::parse($tag, $content) . ']';
        }

        if (!$default) {
            $tag['content'] = $encrypted;
        }

        return parent::parse($tag, $content);
    }


    /**
     * encrypt a given string
     *
     * @param string $email
     * @return string
     */
    protected function _encrypt($email) {

    	$encrypted = '';

    	if ($this->getConfig('encrypt')) {
    		$length = mb_strlen($email);

    		if ($length > 0) {
    			for ($i = 0; $i < $length; ++$i) {
    				$encrypted .= '&#' . ord(mb_substr($email, $i, 1)) . ';';
    			}
    		}
    	} else {
    		$encrypted = $email;
    	}

    	return $encrypted;
    }


    /**
     * Strip a node but keep the email regardless of location.
     *
     * @param array $tag
     * @param string $content
     * @return string
     */
    public function strip(array $tag, $content) {
        $email = isset($tag['attributes']['default']) ? $tag['attributes']['default'] : $content;

        return parent::strip($tag, $email);
    }

}