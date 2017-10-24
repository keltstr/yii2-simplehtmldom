<?php
/* ==="PHP Simple HTML DOM Parser" yii2 extension=== */
/**===framework Yii2 
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
 /*=== Adapted origin library to Yii2
 * origin Library "PHP Simple HTML DOM Parser" @version 1.5 ($Rev: 210 - 2014-05-28) from http://sourceforge.net/p/simplehtmldom/code/210/tree/trunk/simple_html_dom.php
 * *@author Serhat (github.com/serhatozles) Turkey serhatozles[]gmail.com http://www.nippy.in
 * *@author Keltstr (github.com/keltstr) Europe/Minsk keltstr[]gmail.com
 */
/**=== origin Library "PHP Simple HTML DOM Parser" 
 * Website: http://sourceforge.net/projects/simplehtmldom/
 * Additional projects that may be used: http://sourceforge.net/projects/debugobject/
 * Acknowledge: Jose Solorzano (https://sourceforge.net/projects/php-html/)
 * Contributions by:
 *	 Yousuke Kumakura (Attribute filters)
 *	 Vadim Voituk (Negative indexes supports of "find" method)
 *	 Antcs (Constructor with automatically load contents either text or file/url)
 *
 * all affected sections have comments starting with "PaperG"
 *
 * Paperg - Added case insensitive testing of the value of the selector.
 * Paperg - Added tag_start for the starting index of tags - NOTE: This works but not accurately.
 *  This tag_start gets counted AFTER \r\n have been crushed out, and after the remove_noice calls so it will not reflect the REAL position of the tag in the source,
 *  it will almost always be smaller by some amount.
 *  We use this to determine how far into the file the tag in question is.  This "percentage will never be accurate as the $dom->size is the "real" number of bytes the dom was created from.
 *  but for most purposes, it's a really good estimation.
 * Paperg - Added the forceTagsClosed to the dom constructor.  Forcing tags closed is great for malformed html, but it CAN lead to parsing errors.
 * Allow the user to tell us how much they trust the html.
 * Paperg add the text and plaintext to the selectors for the find syntax.  plaintext implies text in the innertext of a node.  text implies that the tag is a text node.
 * This allows for us to find tags based on the text they contain.
 * Create find_ancestor_tag to see if a tag is - at any level - inside of another specific tag.
 * Paperg: added parse_charset so that we know about the character set of the source document.
 *  NOTE:  If the user's system has a routine called get_last_retrieve_url_contents_content_type availalbe, we will assume it's returning the content-type header from the
 *  last transfer or curl_exec, and we will parse that and use it in preference to any other method of charset detection.
 *
 * Found infinite loop in the case of broken html in restore_noise.  Rewrote to protect from that.
 * PaperG (John Schlick) Added get_display_size for "IMG" tags.
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author S.C. Chen <me578022@gmail.com>
 * @author John Schlick
 * @author Rus Carroll
 * @version 1.5 ($Rev: 210 $)
 * @package PlaceLocalInclude
 * @subpackage simple_html_dom
 */
/**
 * All of the Defines for the classes below.
 * @author S.C. Chen <me578022@gmail.com>
 */
namespace keltstr\simplehtmldom;
define( 'HDOM_TYPE_ELEMENT', 1 );
define( 'HDOM_TYPE_COMMENT', 2 );
define( 'HDOM_TYPE_TEXT', 3 );
define( 'HDOM_TYPE_ENDTAG', 4 );
define( 'HDOM_TYPE_ROOT', 5 );
define( 'HDOM_TYPE_UNKNOWN', 6 );
define( 'HDOM_QUOTE_DOUBLE', 0 );
define( 'HDOM_QUOTE_SINGLE', 1 );
define( 'HDOM_QUOTE_NO', 3 );
define( 'HDOM_INFO_BEGIN', 0 );
define( 'HDOM_INFO_END', 1 );
define( 'HDOM_INFO_QUOTE', 2 );
define( 'HDOM_INFO_SPACE', 3 );
define( 'HDOM_INFO_TEXT', 4 );
define( 'HDOM_INFO_INNER', 5 );
define( 'HDOM_INFO_OUTER', 6 );
define( 'HDOM_INFO_ENDSPACE', 7 );
define( 'DEFAULT_TARGET_CHARSET', 'UTF-8' );
define( 'DEFAULT_BR_TEXT', "\r\n" );
define( 'DEFAULT_SPAN_TEXT', " " );
define( 'MAX_FILE_SIZE', 600000 );
// helper functions
// -----------------------------------------------------------------------------
class SimpleHTMLDom extends \yii\helpers\Inflector
{
    // get html dom from file
    // $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
    public static function file_get_html( $url, $use_include_path = false, $context = null, $offset = 0, $maxLen = -1, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT )
    {
        // We DO force the tags to be terminated.
        $dom      = new simple_html_dom( null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText );
        // For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
        $contents = file_get_contents( $url, $use_include_path, $context, $offset );
        // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
        //$contents = retrieve_url_contents($url);
        if ( empty( $contents ) || strlen( $contents ) > MAX_FILE_SIZE ) {
            return false;
        } //empty( $contents ) || strlen( $contents ) > MAX_FILE_SIZE
        // The second parameter can force the selectors to all be lowercase.
        $dom->load( $contents, $lowercase, $stripRN );
        return $dom;
    }
    // get html dom from string
    public static function str_get_html( $str, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT )
    {
        $dom = new simple_html_dom( null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText );
        if ( empty( $str ) || strlen( $str ) > MAX_FILE_SIZE ) {
            $dom->clear();
            return false;
        } //empty( $str ) || strlen( $str ) > MAX_FILE_SIZE
        $dom->load( $str, $lowercase, $stripRN );
        return $dom;
    }
    // dump html dom tree
    public static function dump_html_tree( $node, $show_attr = true, $deep = 0 )
    {
        $node->dump( $node );
    }
}
/**
 * simple html dom node
 * PaperG - added ability for "find" routine to lowercase the value of the selector.
 * PaperG - added $tag_start to track the start position of the tag in the total byte index
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom_node
{
    public $nodetype = HDOM_TYPE_TEXT;
    public $tag = 'text';
    public $attr = array( );
    public $children = array( );
    public $nodes = array( );
    public $parent = null;
    // The "info" array - see HDOM_INFO_... for what each element contains.
    public $_ = array( );
    public $tag_start = 0;
    private $dom = null;
    function __construct( $dom )
    {
        $this->dom     = $dom;
        $dom->nodes[ ] = $this;
    }
    function __destruct( )
    {
        $this->clear();
    }
    function __toString( )
    {
        return $this->outertext();
    }
    // clean up memory due to php5 circular references memory leak...
    function clear( )
    {
        $this->dom      = null;
        $this->nodes    = null;
        $this->parent   = null;
        $this->children = null;
    }
    // dump node's tree
    function dump( $show_attr = true, $deep = 0 )
    {
        $lead = str_repeat( '	', $deep );
        echo $lead . $this->tag;
        if ( $show_attr && count( $this->attr ) > 0 ) {
            echo '(';
            foreach ( $this->attr as $k => $v )
                echo "[$k]=>\"" . $this->$k . '", ';
            echo ')';
        } //$show_attr && count( $this->attr ) > 0
        echo "\n";
        if ( $this->nodes ) {
            foreach ( $this->nodes as $c ) {
                $c->dump( $show_attr, $deep + 1 );
            } //$this->nodes as $c
        } //$this->nodes
    }
    // Debugging function to dump a single dom node with a bunch of information about it.
    function dump_node( $echo = true )
    {
        $string = $this->tag;
        if ( count( $this->attr ) > 0 ) {
            $string .= '(';
            foreach ( $this->attr as $k => $v ) {
                $string .= "[$k]=>\"" . $this->$k . '", ';
            } //$this->attr as $k => $v
            $string .= ')';
        } //count( $this->attr ) > 0
        if ( count( $this->_ ) > 0 ) {
            $string .= ' $_ (';
            foreach ( $this->_ as $k => $v ) {
                if ( is_array( $v ) ) {
                    $string .= "[$k]=>(";
                    foreach ( $v as $k2 => $v2 ) {
                        $string .= "[$k2]=>\"" . $v2 . '", ';
                    } //$v as $k2 => $v2
                    $string .= ")";
                } //is_array( $v )
                else {
                    $string .= "[$k]=>\"" . $v . '", ';
                }
            } //$this->_ as $k => $v
            $string .= ")";
        } //count( $this->_ ) > 0
        if ( isset( $this->text ) ) {
            $string .= " text: (" . $this->text . ")";
        } //isset( $this->text )
        $string .= " HDOM_INNER_INFO: '";
        if ( isset( $node->_[ HDOM_INFO_INNER ] ) ) {
            $string .= $node->_[ HDOM_INFO_INNER ] . "'";
        } //isset( $node->_[ HDOM_INFO_INNER ] )
        else {
            $string .= ' NULL ';
        }
        $string .= " children: " . count( $this->children );
        $string .= " nodes: " . count( $this->nodes );
        $string .= " tag_start: " . $this->tag_start;
        $string .= "\n";
        if ( $echo ) {
            echo $string;
            return;
        } //$echo
        else {
            return $string;
        }
    }
    // returns the parent of node
    // If a node is passed in, it will reset the parent of the current node to that one.
    function parent( $parent = null )
    {
        // I am SURE that this doesn't work properly.
        // It fails to unset the current node from it's current parents nodes or children list first.
        if ( $parent !== null ) {
            $this->parent              = $parent;
            $this->parent->nodes[ ]    = $this;
            $this->parent->children[ ] = $this;
        } //$parent !== null
        return $this->parent;
    }
    // verify that node has children
    function has_child( )
    {
        return !empty( $this->children );
    }
    // returns children of node
    function children( $idx = -1 )
    {
        if ( $idx === -1 ) {
            return $this->children;
        } //$idx === -1
        if ( isset( $this->children[ $idx ] ) ) {
            return $this->children[ $idx ];
        } //isset( $this->children[ $idx ] )
        return null;
    }
    // returns the first child of node
    function first_child( )
    {
        if ( count( $this->children ) > 0 ) {
            return $this->children[ 0 ];
        } //count( $this->children ) > 0
        return null;
    }
    // returns the last child of node
    function last_child( )
    {
        if ( ( $count = count( $this->children ) ) > 0 ) {
            return $this->children[ $count - 1 ];
        } //( $count = count( $this->children ) ) > 0
        return null;
    }
    // returns the next sibling of node
    function next_sibling( )
    {
        if ( $this->parent === null ) {
            return null;
        } //$this->parent === null
        $idx   = 0;
        $count = count( $this->parent->children );
        while ( $idx < $count && $this !== $this->parent->children[ $idx ] ) {
            ++$idx;
        } //$idx < $count && $this !== $this->parent->children[ $idx ]
        if ( ++$idx >= $count ) {
            return null;
        } //++$idx >= $count
        return $this->parent->children[ $idx ];
    }
    // returns the previous sibling of node
    function prev_sibling( )
    {
        if ( $this->parent === null )
            return null;
        $idx   = 0;
        $count = count( $this->parent->children );
        while ( $idx < $count && $this !== $this->parent->children[ $idx ] )
            ++$idx;
        if ( --$idx < 0 )
            return null;
        return $this->parent->children[ $idx ];
    }
    // function to locate a specific ancestor tag in the path to the root.
    function find_ancestor_tag( $tag )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        // Start by including ourselves in the comparison.
        $returnDom = $this;
        while ( !is_null( $returnDom ) ) {
            if ( is_object( $debug_object ) ) {
                $debug_object->debug_log( 2, "Current tag is: " . $returnDom->tag );
            } //is_object( $debug_object )
            if ( $returnDom->tag == $tag ) {
                break;
            } //$returnDom->tag == $tag
            $returnDom = $returnDom->parent;
        } //!is_null( $returnDom )
        return $returnDom;
    }
    // get dom node's inner html
    function innertext( )
    {
        if ( isset( $this->_[ HDOM_INFO_INNER ] ) )
            return $this->_[ HDOM_INFO_INNER ];
        if ( isset( $this->_[ HDOM_INFO_TEXT ] ) )
            return $this->dom->restore_noise( $this->_[ HDOM_INFO_TEXT ] );
        $ret = '';
        foreach ( $this->nodes as $n )
            $ret .= $n->outertext();
        return $ret;
    }
    // get dom node's outer text (with tag)
    function outertext( )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $text = '';
            if ( $this->tag == 'text' ) {
                if ( !empty( $this->text ) ) {
                    $text = " with text: " . $this->text;
                } //!empty( $this->text )
            } //$this->tag == 'text'
            $debug_object->debug_log( 1, 'Innertext of tag: ' . $this->tag . $text );
        } //is_object( $debug_object )
        if ( $this->tag === 'root' )
            return $this->innertext();
        // trigger callback
        if ( $this->dom && $this->dom->callback !== null ) {
            call_user_func_array( $this->dom->callback, array(
                 $this
            ) );
        } //$this->dom && $this->dom->callback !== null
        if ( isset( $this->_[ HDOM_INFO_OUTER ] ) )
            return $this->_[ HDOM_INFO_OUTER ];
        if ( isset( $this->_[ HDOM_INFO_TEXT ] ) )
            return $this->dom->restore_noise( $this->_[ HDOM_INFO_TEXT ] );
        // render begin tag
        if ( $this->dom && $this->dom->nodes[ $this->_[ HDOM_INFO_BEGIN ] ] ) {
            $ret = $this->dom->nodes[ $this->_[ HDOM_INFO_BEGIN ] ]->makeup();
        } //$this->dom && $this->dom->nodes[ $this->_[ HDOM_INFO_BEGIN ] ]
        else {
            $ret = "";
        }
        // render inner text
        if ( isset( $this->_[ HDOM_INFO_INNER ] ) ) {
            // If it's a br tag...  don't return the HDOM_INNER_INFO that we may or may not have added.
            if ( $this->tag != "br" ) {
                $ret .= $this->_[ HDOM_INFO_INNER ];
            } //$this->tag != "br"
        } //isset( $this->_[ HDOM_INFO_INNER ] )
        else {
            if ( $this->nodes ) {
                foreach ( $this->nodes as $n ) {
                    $ret .= $this->convert_text( $n->outertext() );
                } //$this->nodes as $n
            } //$this->nodes
        }
        // render end tag
        if ( isset( $this->_[ HDOM_INFO_END ] ) && $this->_[ HDOM_INFO_END ] != 0 )
            $ret .= '</' . $this->tag . '>';
        return $ret;
    }
    // get dom node's plain text
    function text( )
    {
        if ( isset( $this->_[ HDOM_INFO_INNER ] ) )
            return $this->_[ HDOM_INFO_INNER ];
        switch ( $this->nodetype ) {
            case HDOM_TYPE_TEXT:
                return $this->dom->restore_noise( $this->_[ HDOM_INFO_TEXT ] );
            case HDOM_TYPE_COMMENT:
                return '';
            case HDOM_TYPE_UNKNOWN:
                return '';
        } //$this->nodetype
        if ( strcasecmp( $this->tag, 'script' ) === 0 )
            return '';
        if ( strcasecmp( $this->tag, 'style' ) === 0 )
            return '';
        $ret = '';
        // In rare cases, (always node type 1 or HDOM_TYPE_ELEMENT - observed for some span tags, and some p tags) $this->nodes is set to NULL.
        // NOTE: This indicates that there is a problem where it's set to NULL without a clear happening.
        // WHY is this happening?
        if ( !is_null( $this->nodes ) ) {
            foreach ( $this->nodes as $n ) {
                $ret .= $this->convert_text( $n->text() );
            } //$this->nodes as $n
            // If this node is a span... add a space at the end of it so multiple spans don't run into each other.  This is plaintext after all.
            if ( $this->tag == "span" ) {
                $ret .= $this->dom->default_span_text;
            } //$this->tag == "span"
        } //!is_null( $this->nodes )
        return $ret;
    }
    function xmltext( )
    {
        $ret = $this->innertext();
        $ret = str_ireplace( '<![CDATA[', '', $ret );
        $ret = str_replace( ']]>', '', $ret );
        return $ret;
    }
    // build node's text with tag
    function makeup( )
    {
        // text, comment, unknown
        if ( isset( $this->_[ HDOM_INFO_TEXT ] ) )
            return $this->dom->restore_noise( $this->_[ HDOM_INFO_TEXT ] );
        $ret = '<' . $this->tag;
        $i   = -1;
        foreach ( $this->attr as $key => $val ) {
            ++$i;
            // skip removed attribute
            if ( $val === null || $val === false )
                continue;
            $ret .= $this->_[ HDOM_INFO_SPACE ][ $i ][ 0 ];
            //no value attr: nowrap, checked selected...
            if ( $val === true )
                $ret .= $key;
            else {
                switch ( $this->_[ HDOM_INFO_QUOTE ][ $i ] ) {
                    case HDOM_QUOTE_DOUBLE:
                        $quote = '"';
                        break;
                    case HDOM_QUOTE_SINGLE:
                        $quote = '\'';
                        break;
                    default:
                        $quote = '';
                } //$this->_[ HDOM_INFO_QUOTE ][ $i ]
                $ret .= $key . $this->_[ HDOM_INFO_SPACE ][ $i ][ 1 ] . '=' . $this->_[ HDOM_INFO_SPACE ][ $i ][ 2 ] . $quote . $val . $quote;
            }
        } //$this->attr as $key => $val
        $ret = $this->dom->restore_noise( $ret );
        return $ret . $this->_[ HDOM_INFO_ENDSPACE ] . '>';
    }
    // find elements by css selector
    //PaperG - added ability for find to lowercase the value of the selector.
    function find( $selector, $idx = null, $lowercase = false )
    {
        $selectors = $this->parse_selector( $selector );
        if ( ( $count = count( $selectors ) ) === 0 )
            return array( );
        $found_keys = array( );
        // find each selector
        for ( $c = 0; $c < $count; ++$c ) {
            // The change on the below line was documented on the sourceforge code tracker id 2788009
            // used to be: if (($levle=count($selectors[0]))===0) return array();
            if ( ( $levle = count( $selectors[ $c ] ) ) === 0 )
                return array( );
            if ( !isset( $this->_[ HDOM_INFO_BEGIN ] ) )
                return array( );
            $head = array(
                 $this->_[ HDOM_INFO_BEGIN ] => 1
            );
            // handle descendant selectors, no recursive!
            for ( $l = 0; $l < $levle; ++$l ) {
                $ret = array( );
                foreach ( $head as $k => $v ) {
                    $n = ( $k === -1 ) ? $this->dom->root : $this->dom->nodes[ $k ];
                    //PaperG - Pass this optional parameter on to the seek function.
                    $n->seek( $selectors[ $c ][ $l ], $ret, $lowercase );
                } //$head as $k => $v
                $head = $ret;
            } //$l = 0; $l < $levle; ++$l
            foreach ( $head as $k => $v ) {
                if ( !isset( $found_keys[ $k ] ) ) {
                    $found_keys[ $k ] = 1;
                } //!isset( $found_keys[ $k ] )
            } //$head as $k => $v
        } //$c = 0; $c < $count; ++$c
        // sort keys
        ksort( $found_keys );
        $found = array( );
        foreach ( $found_keys as $k => $v )
            $found[ ] = $this->dom->nodes[ $k ];
        // return nth-element or array
        if ( is_null( $idx ) )
            return $found;
        else if ( $idx < 0 )
            $idx = count( $found ) + $idx;
        return ( isset( $found[ $idx ] ) ) ? $found[ $idx ] : null;
    }
    // seek for given conditions
    // PaperG - added parameter to allow for case insensitive testing of the value of a selector.
    protected function seek( $selector, &$ret, $lowercase = false )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        list( $tag, $key, $val, $exp, $no_key ) = $selector;
        // xpath index
        if ( $tag && $key && is_numeric( $key ) ) {
            $count = 0;
            foreach ( $this->children as $c ) {
                if ( $tag === '*' || $tag === $c->tag ) {
                    if ( ++$count == $key ) {
                        $ret[ $c->_[ HDOM_INFO_BEGIN ] ] = 1;
                        return;
                    } //++$count == $key
                } //$tag === '*' || $tag === $c->tag
            } //$this->children as $c
            return;
        } //$tag && $key && is_numeric( $key )
        $end = ( !empty( $this->_[ HDOM_INFO_END ] ) ) ? $this->_[ HDOM_INFO_END ] : 0;
        if ( $end == 0 ) {
            $parent = $this->parent;
            while ( !isset( $parent->_[ HDOM_INFO_END ] ) && $parent !== null ) {
                $end -= 1;
                $parent = $parent->parent;
            } //!isset( $parent->_[ HDOM_INFO_END ] ) && $parent !== null
            $end += $parent->_[ HDOM_INFO_END ];
        } //$end == 0
        for ( $i = $this->_[ HDOM_INFO_BEGIN ] + 1; $i < $end; ++$i ) {
            $node = $this->dom->nodes[ $i ];
            $pass = true;
            if ( $tag === '*' && !$key ) {
                if ( in_array( $node, $this->children, true ) )
                    $ret[ $i ] = 1;
                continue;
            } //$tag === '*' && !$key
            // compare tag
            if ( $tag && $tag != $node->tag && $tag !== '*' ) {
                $pass = false;
            } //$tag && $tag != $node->tag && $tag !== '*'
            // compare key
            if ( $pass && $key ) {
                if ( $no_key ) {
                    if ( isset( $node->attr[ $key ] ) )
                        $pass = false;
                } //$no_key
                else {
                    if ( ( $key != "plaintext" ) && !isset( $node->attr[ $key ] ) )
                        $pass = false;
                }
            } //$pass && $key
            // compare value
            if ( $pass && $key && $val && $val !== '*' ) {
                // If they have told us that this is a "plaintext" search then we want the plaintext of the node - right?
                if ( $key == "plaintext" ) {
                    // $node->plaintext actually returns $node->text();
                    $nodeKeyValue = $node->text();
                } //$key == "plaintext"
                else {
                    // this is a normal search, we want the value of that attribute of the tag.
                    $nodeKeyValue = $node->attr[ $key ];
                }
                if ( is_object( $debug_object ) ) {
                    $debug_object->debug_log( 2, "testing node: " . $node->tag . " for attribute: " . $key . $exp . $val . " where nodes value is: " . $nodeKeyValue );
                } //is_object( $debug_object )
                //PaperG - If lowercase is set, do a case insensitive test of the value of the selector.
                if ( $lowercase ) {
                    $check = $this->match( $exp, strtolower( $val ), strtolower( $nodeKeyValue ) );
                } //$lowercase
                else {
                    $check = $this->match( $exp, $val, $nodeKeyValue );
                }
                if ( is_object( $debug_object ) ) {
                    $debug_object->debug_log( 2, "after match: " . ( $check ? "true" : "false" ) );
                } //is_object( $debug_object )
                // handle multiple class
                if ( !$check && strcasecmp( $key, 'class' ) === 0 ) {
                    foreach ( explode( ' ', $node->attr[ $key ] ) as $k ) {
                        // Without this, there were cases where leading, trailing, or double spaces lead to our comparing blanks - bad form.
                        if ( !empty( $k ) ) {
                            if ( $lowercase ) {
                                $check = $this->match( $exp, strtolower( $val ), strtolower( $k ) );
                            } //$lowercase
                            else {
                                $check = $this->match( $exp, $val, $k );
                            }
                            if ( $check )
                                break;
                        } //!empty( $k )
                    } //explode( ' ', $node->attr[ $key ] ) as $k
                } //!$check && strcasecmp( $key, 'class' ) === 0
                if ( !$check )
                    $pass = false;
            } //$pass && $key && $val && $val !== '*'
            if ( $pass )
                $ret[ $i ] = 1;
            unset( $node );
        } //$i = $this->_[ HDOM_INFO_BEGIN ] + 1; $i < $end; ++$i
        // It's passed by reference so this is actually what this function returns.
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log( 1, "EXIT - ret: ", $ret );
        } //is_object( $debug_object )
    }
    protected function match( $exp, $pattern, $value )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        switch ( $exp ) {
            case '=':
                return ( $value === $pattern );
            case '!=':
                return ( $value !== $pattern );
            case '^=':
                return preg_match( "/^" . preg_quote( $pattern, '/' ) . "/", $value );
            case '$=':
                return preg_match( "/" . preg_quote( $pattern, '/' ) . "$/", $value );
            case '*=':
                if ( $pattern[ 0 ] == '/' ) {
                    return preg_match( $pattern, $value );
                } //$pattern[ 0 ] == '/'
                return preg_match( "/" . $pattern . "/i", $value );
        } //$exp
        return false;
    }
    protected function parse_selector( $selector_string )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        // pattern of CSS selectors, modified from mootools
        // Paperg: Add the colon to the attrbute, so that it properly finds <tag attr:ibute="something" > like google does.
        // Note: if you try to look at this attribute, yo MUST use getAttribute since $dom->x:y will fail the php syntax check.
        // Notice the \[ starting the attbute?  and the @? following?  This implies that an attribute can begin with an @ sign that is not captured.
        // This implies that an html attribute specifier may start with an @ sign that is NOT captured by the expression.
        // farther study is required to determine of this should be documented or removed.
        //		$pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        $pattern = "/([\w-:\*]*)(?:\#([\w-]+)|\.([\w-]+))?(?:\[@?(!?[\w-:]+)(?:([!*^$]?=)[\"']?(.*?)[\"']?)?\])?([\/, ]+)/is";
        preg_match_all( $pattern, trim( $selector_string ) . ' ', $matches, PREG_SET_ORDER );
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log( 2, "Matches Array: ", $matches );
        } //is_object( $debug_object )
        $selectors = array( );
        $result    = array( );
        //print_r($matches);
        foreach ( $matches as $m ) {
            $m[ 0 ] = trim( $m[ 0 ] );
            if ( $m[ 0 ] === '' || $m[ 0 ] === '/' || $m[ 0 ] === '//' )
                continue;
            // for browser generated xpath
            if ( $m[ 1 ] === 'tbody' )
                continue;
            list( $tag, $key, $val, $exp, $no_key ) = array(
                 $m[ 1 ],
                null,
                null,
                '=',
                false
            );
            if ( !empty( $m[ 2 ] ) ) {
                $key = 'id';
                $val = $m[ 2 ];
            } //!empty( $m[ 2 ] )
            if ( !empty( $m[ 3 ] ) ) {
                $key = 'class';
                $val = $m[ 3 ];
            } //!empty( $m[ 3 ] )
            if ( !empty( $m[ 4 ] ) ) {
                $key = $m[ 4 ];
            } //!empty( $m[ 4 ] )
            if ( !empty( $m[ 5 ] ) ) {
                $exp = $m[ 5 ];
            } //!empty( $m[ 5 ] )
            if ( !empty( $m[ 6 ] ) ) {
                $val = $m[ 6 ];
            } //!empty( $m[ 6 ] )
            // convert to lowercase
            if ( $this->dom->lowercase ) {
                $tag = strtolower( $tag );
                $key = strtolower( $key );
            } //$this->dom->lowercase
            //elements that do NOT have the specified attribute
            if ( isset( $key[ 0 ] ) && $key[ 0 ] === '!' ) {
                $key    = substr( $key, 1 );
                $no_key = true;
            } //isset( $key[ 0 ] ) && $key[ 0 ] === '!'
            $result[ ] = array(
                 $tag,
                $key,
                $val,
                $exp,
                $no_key
            );
            if ( trim( $m[ 7 ] ) === ',' ) {
                $selectors[ ] = $result;
                $result       = array( );
            } //trim( $m[ 7 ] ) === ','
        } //$matches as $m
        if ( count( $result ) > 0 )
            $selectors[ ] = $result;
        return $selectors;
    }
    function __get( $name )
    {
        if ( isset( $this->attr[ $name ] ) ) {
            return $this->convert_text( $this->attr[ $name ] );
        } //isset( $this->attr[ $name ] )
        switch ( $name ) {
            case 'outertext':
                return $this->outertext();
            case 'innertext':
                return $this->innertext();
            case 'plaintext':
                return $this->text();
            case 'xmltext':
                return $this->xmltext();
            default:
                return array_key_exists( $name, $this->attr );
        } //$name
    }
    function __set( $name, $value )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        switch ( $name ) {
            case 'outertext':
                return $this->_[ HDOM_INFO_OUTER ] = $value;
            case 'innertext':
                if ( isset( $this->_[ HDOM_INFO_TEXT ] ) )
                    return $this->_[ HDOM_INFO_TEXT ] = $value;
                return $this->_[ HDOM_INFO_INNER ] = $value;
        } //$name
        if ( !isset( $this->attr[ $name ] ) ) {
            $this->_[ HDOM_INFO_SPACE ][ ] = array(
                 ' ',
                '',
                ''
            );
            $this->_[ HDOM_INFO_QUOTE ][ ] = HDOM_QUOTE_DOUBLE;
        } //!isset( $this->attr[ $name ] )
        $this->attr[ $name ] = $value;
    }
    function __isset( $name )
    {
        switch ( $name ) {
            case 'outertext':
                return true;
            case 'innertext':
                return true;
            case 'plaintext':
                return true;
        } //$name
        //no value attr: nowrap, checked selected...
        return ( array_key_exists( $name, $this->attr ) ) ? true : isset( $this->attr[ $name ] );
    }
    function __unset( $name )
    {
        if ( isset( $this->attr[ $name ] ) )
            unset( $this->attr[ $name ] );
    }
    // PaperG - Function to convert the text from one character set to another if the two sets are not the same.
    function convert_text( $text )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        $converted_text = $text;
        $sourceCharset  = "";
        $targetCharset  = "";
        if ( $this->dom ) {
            $sourceCharset = strtoupper( $this->dom->_charset );
            $targetCharset = strtoupper( $this->dom->_target_charset );
        } //$this->dom
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log( 3, "source charset: " . $sourceCharset . " target charaset: " . $targetCharset );
        } //is_object( $debug_object )
        if ( !empty( $sourceCharset ) && !empty( $targetCharset ) && ( strcasecmp( $sourceCharset, $targetCharset ) != 0 ) ) {
            // Check if the reported encoding could have been incorrect and the text is actually already UTF-8
            if ( ( strcasecmp( $targetCharset, 'UTF-8' ) == 0 ) && ( $this->is_utf8( $text ) ) ) {
                $converted_text = $text;
            } //( strcasecmp( $targetCharset, 'UTF-8' ) == 0 ) && ( $this->is_utf8( $text ) )
            else {
                $converted_text = iconv( $sourceCharset, $targetCharset, $text );
            }
        } //!empty( $sourceCharset ) && !empty( $targetCharset ) && ( strcasecmp( $sourceCharset, $targetCharset ) != 0 )
        // Lets make sure that we don't have that silly BOM issue with any of the utf-8 text we output.
        if ( $targetCharset == 'UTF-8' ) {
            if ( substr( $converted_text, 0, 3 ) == "\xef\xbb\xbf" ) {
                $converted_text = substr( $converted_text, 3 );
            } //substr( $converted_text, 0, 3 ) == "\xef\xbb\xbf"
            if ( substr( $converted_text, -3 ) == "\xef\xbb\xbf" ) {
                $converted_text = substr( $converted_text, 0, -3 );
            } //substr( $converted_text, -3 ) == "\xef\xbb\xbf"
        } //$targetCharset == 'UTF-8'
        return $converted_text;
    }
    /**
     * Returns true if $string is valid UTF-8 and false otherwise.
     *
     * @param mixed $str String to be tested
     * @return boolean
     */
    static function is_utf8( $str )
    {
        $c    = 0;
        $b    = 0;
        $bits = 0;
        $len  = strlen( $str );
        for ( $i = 0; $i < $len; $i++ ) {
            $c = ord( $str[ $i ] );
            if ( $c > 128 ) {
                if ( ( $c >= 254 ) )
                    return false;
                elseif ( $c >= 252 )
                    $bits = 6;
                elseif ( $c >= 248 )
                    $bits = 5;
                elseif ( $c >= 240 )
                    $bits = 4;
                elseif ( $c >= 224 )
                    $bits = 3;
                elseif ( $c >= 192 )
                    $bits = 2;
                else
                    return false;
                if ( ( $i + $bits ) > $len )
                    return false;
                while ( $bits > 1 ) {
                    $i++;
                    $b = ord( $str[ $i ] );
                    if ( $b < 128 || $b > 191 )
                        return false;
                    $bits--;
                } //$bits > 1
            } //$c > 128
        } //$i = 0; $i < $len; $i++
        return true;
    }
    /*
    function is_utf8($string)
    {
    //this is buggy
    return (utf8_encode(utf8_decode($string)) == $string);
    }
    */
    /**
     * Function to try a few tricks to determine the displayed size of an img on the page.
     * NOTE: This will ONLY work on an IMG tag. Returns FALSE on all other tag types.
     *
     * @author John Schlick
     * @version April 19 2012
     * @return array an array containing the 'height' and 'width' of the image on the page or -1 if we can't figure it out.
     */
    function get_display_size( )
    {
        global $debug_object;
        $width  = -1;
        $height = -1;
        if ( $this->tag !== 'img' ) {
            return false;
        } //$this->tag !== 'img'
        // See if there is aheight or width attribute in the tag itself.
        if ( isset( $this->attr[ 'width' ] ) ) {
            $width = $this->attr[ 'width' ];
        } //isset( $this->attr[ 'width' ] )
        if ( isset( $this->attr[ 'height' ] ) ) {
            $height = $this->attr[ 'height' ];
        } //isset( $this->attr[ 'height' ] )
        // Now look for an inline style.
        if ( isset( $this->attr[ 'style' ] ) ) {
            // Thanks to user gnarf from stackoverflow for this regular expression.
            $attributes = array( );
            preg_match_all( "/([\w-]+)\s*:\s*([^;]+)\s*;?/", $this->attr[ 'style' ], $matches, PREG_SET_ORDER );
            foreach ( $matches as $match ) {
                $attributes[ $match[ 1 ] ] = $match[ 2 ];
            } //$matches as $match
            // If there is a width in the style attributes:
            if ( isset( $attributes[ 'width' ] ) && $width == -1 ) {
                // check that the last two characters are px (pixels)
                if ( strtolower( substr( $attributes[ 'width' ], -2 ) ) == 'px' ) {
                    $proposed_width = substr( $attributes[ 'width' ], 0, -2 );
                    // Now make sure that it's an integer and not something stupid.
                    if ( filter_var( $proposed_width, FILTER_VALIDATE_INT ) ) {
                        $width = $proposed_width;
                    } //filter_var( $proposed_width, FILTER_VALIDATE_INT )
                } //strtolower( substr( $attributes[ 'width' ], -2 ) ) == 'px'
            } //isset( $attributes[ 'width' ] ) && $width == -1
            // If there is a width in the style attributes:
            if ( isset( $attributes[ 'height' ] ) && $height == -1 ) {
                // check that the last two characters are px (pixels)
                if ( strtolower( substr( $attributes[ 'height' ], -2 ) ) == 'px' ) {
                    $proposed_height = substr( $attributes[ 'height' ], 0, -2 );
                    // Now make sure that it's an integer and not something stupid.
                    if ( filter_var( $proposed_height, FILTER_VALIDATE_INT ) ) {
                        $height = $proposed_height;
                    } //filter_var( $proposed_height, FILTER_VALIDATE_INT )
                } //strtolower( substr( $attributes[ 'height' ], -2 ) ) == 'px'
            } //isset( $attributes[ 'height' ] ) && $height == -1
        } //isset( $this->attr[ 'style' ] )
        // Future enhancement:
        // Look in the tag to see if there is a class or id specified that has a height or width attribute to it.
        // Far future enhancement
        // Look at all the parent tags of this image to see if they specify a class or id that has an img selector that specifies a height or width
        // Note that in this case, the class or id will have the img subselector for it to apply to the image.
        // ridiculously far future development
        // If the class or id is specified in a SEPARATE css file thats not on the page, go get it and do what we were just doing for the ones on the page.
        $result = array(
             'height' => $height,
            'width' => $width
        );
        return $result;
    }
    // camel naming conventions
    function getAllAttributes( )
    {
        return $this->attr;
    }
    function getAttribute( $name )
    {
        return $this->__get( $name );
    }
    function setAttribute( $name, $value )
    {
        $this->__set( $name, $value );
    }
    function hasAttribute( $name )
    {
        return $this->__isset( $name );
    }
    function removeAttribute( $name )
    {
        $this->__set( $name, null );
    }
    function getElementById( $id )
    {
        return $this->find( "#$id", 0 );
    }
    function getElementsById( $id, $idx = null )
    {
        return $this->find( "#$id", $idx );
    }
    function getElementByTagName( $name )
    {
        return $this->find( $name, 0 );
    }
    function getElementsByTagName( $name, $idx = null )
    {
        return $this->find( $name, $idx );
    }
    function parentNode( )
    {
        return $this->parent();
    }
    function childNodes( $idx = -1 )
    {
        return $this->children( $idx );
    }
    function firstChild( )
    {
        return $this->first_child();
    }
    function lastChild( )
    {
        return $this->last_child();
    }
    function nextSibling( )
    {
        return $this->next_sibling();
    }
    function previousSibling( )
    {
        return $this->prev_sibling();
    }
    function hasChildNodes( )
    {
        return $this->has_child();
    }
    function nodeName( )
    {
        return $this->tag;
    }
    function appendChild( $node )
    {
        $node->parent( $this );
        return $node;
    }
}
/**
 * simple html dom parser
 * Paperg - in the find routine: allow us to specify that we want case insensitive testing of the value of the selector.
 * Paperg - change $size from protected to public so we can easily access it
 * Paperg - added ForceTagsClosed in the constructor which tells us whether we trust the html or not.  Default is to NOT trust it.
 *
 * @package PlaceLocalInclude
 */
class simple_html_dom
{
    public $root = null;
    public $nodes = array( );
    public $callback = null;
    public $lowercase = false;
    // Used to keep track of how large the text was when we started.
    public $original_size;
    public $size;
    protected $pos;
    protected $doc;
    protected $char;
    protected $cursor;
    protected $parent;
    protected $noise = array( );
    protected $token_blank = " \t\r\n";
    protected $token_equal = ' =/>';
    protected $token_slash = " />\r\n\t";
    protected $token_attr = ' >';
    // Note that this is referenced by a child node, and so it needs to be public for that node to see this information.
    public $_charset = '';
    public $_target_charset = '';
    protected $default_br_text = "";
    public $default_span_text = "";
    // use isset instead of in_array, performance boost about 30%...
    protected $self_closing_tags = array( 'img' => 1, 'br' => 1, 'input' => 1, 'meta' => 1, 'link' => 1, 'hr' => 1, 'base' => 1, 'embed' => 1, 'spacer' => 1 );
    protected $block_tags = array( 'root' => 1, 'body' => 1, 'form' => 1, 'div' => 1, 'span' => 1, 'table' => 1 );
    // Known sourceforge issue #2977341
    // B tags that are not closed cause us to return everything to the end of the document.
    protected $optional_closing_tags = array( 'tr' => array( 'tr' => 1, 'td' => 1, 'th' => 1 ), 'th' => array( 'th' => 1 ), 'td' => array( 'td' => 1 ), 'li' => array( 'li' => 1 ), 'dt' => array( 'dt' => 1, 'dd' => 1 ), 'dd' => array( 'dd' => 1, 'dt' => 1 ), 'dl' => array( 'dd' => 1, 'dt' => 1 ), 'p' => array( 'p' => 1 ), 'nobr' => array( 'nobr' => 1 ), 'b' => array( 'b' => 1 ), 'option' => array( 'option' => 1 ) );
    function __construct( $str = null, $lowercase = true, $forceTagsClosed = true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT )
    {
        if ( $str ) {
            if ( preg_match( "/^http:\/\//i", $str ) || is_file( $str ) ) {
                $this->load_file( $str );
            } //preg_match( "/^http:\/\//i", $str ) || is_file( $str )
            else {
                $this->load( $str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText );
            }
        } //$str
        // Forcing tags to be closed implies that we don't trust the html, but it can lead to parsing errors if we SHOULD trust the html.
        if ( !$forceTagsClosed ) {
            $this->optional_closing_array = array( );
        } //!$forceTagsClosed
        $this->_target_charset = $target_charset;
    }
    function __destruct( )
    {
        $this->clear();
    }
    // load html from string
    function load( $str, $lowercase = true, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT )
    {
        global $debug_object;
        // prepare
        $this->prepare( $str, $lowercase, $stripRN, $defaultBRText, $defaultSpanText );
        // strip out cdata
        $this->remove_noise( "'<!\[CDATA\[(.*?)\]\]>'is", true );
        // strip out comments
        $this->remove_noise( "'<!--(.*?)-->'is" );
        // Per sourceforge http://sourceforge.net/tracker/?func=detail&aid=2949097&group_id=218559&atid=1044037
        // Script tags removal now preceeds style tag removal.
        // strip out <script> tags
        $this->remove_noise( "'<\s*script[^>]*[^/]>(.*?)<\s*/\s*script\s*>'is" );
        $this->remove_noise( "'<\s*script\s*>(.*?)<\s*/\s*script\s*>'is" );
        // strip out <style> tags
        $this->remove_noise( "'<\s*style[^>]*[^/]>(.*?)<\s*/\s*style\s*>'is" );
        $this->remove_noise( "'<\s*style\s*>(.*?)<\s*/\s*style\s*>'is" );
        // strip out preformatted tags
        $this->remove_noise( "'<\s*(?:code)[^>]*>(.*?)<\s*/\s*(?:code)\s*>'is" );
        // strip out server side scripts
        $this->remove_noise( "'(<\?)(.*?)(\?>)'s", true );
        // strip smarty scripts
        $this->remove_noise( "'(\{\w)(.*?)(\})'s", true );
        // parsing
        while ( $this->parse() );
        // end
        $this->root->_[ HDOM_INFO_END ] = $this->cursor;
        $this->parse_charset();
        // make load function chainable
        return $this;
    }
    // load html from file
    function load_file( )
    {
        $args = func_get_args();
        $this->load( call_user_func_array( 'file_get_contents', $args ), true );
        // Throw an error if we can't properly load the dom.
        if ( ( $error = error_get_last() ) !== null ) {
            $this->clear();
            return false;
        } //( $error = error_get_last() ) !== null
    }
    // set callback function
    function set_callback( $function_name )
    {
        $this->callback = $function_name;
    }
    // remove callback function
    function remove_callback( )
    {
        $this->callback = null;
    }
    // save dom as string
    function save( $filepath = '' )
    {
        $ret = $this->root->innertext();
        if ( $filepath !== '' )
            file_put_contents( $filepath, $ret, LOCK_EX );
        return $ret;
    }
    // find dom node by css selector
    // Paperg - allow us to specify that we want case insensitive testing of the value of the selector.
    function find( $selector, $idx = null, $lowercase = false )
    {
        return $this->root->find( $selector, $idx, $lowercase );
    }
    // clean up memory due to php5 circular references memory leak...
    function clear( )
    {
        foreach ( $this->nodes as $n ) {
            $n->clear();
            $n = null;
        } //$this->nodes as $n
        // This add next line is documented in the sourceforge repository. 2977248 as a fix for ongoing memory leaks that occur even with the use of clear.
        if ( isset( $this->children ) )
            foreach ( $this->children as $n ) {
                $n->clear();
                $n = null;
            } //$this->children as $n
        if ( isset( $this->parent ) ) {
            $this->parent->clear();
            unset( $this->parent );
        } //isset( $this->parent )
        if ( isset( $this->root ) ) {
            $this->root->clear();
            unset( $this->root );
        } //isset( $this->root )
        unset( $this->doc );
        unset( $this->noise );
    }
    function dump( $show_attr = true )
    {
        $this->root->dump( $show_attr );
    }
    // prepare HTML data and init everything
    protected function prepare( $str, $lowercase = true, $stripRN = true, $defaultBRText = DEFAULT_BR_TEXT, $defaultSpanText = DEFAULT_SPAN_TEXT )
    {
        $this->clear();
        // set the length of content before we do anything to it.
        $this->size          = strlen( $str );
        // Save the original size of the html that we got in.  It might be useful to someone.
        $this->original_size = $this->size;
        //before we save the string as the doc...  strip out the \r \n's if we are told to.
        if ( $stripRN ) {
            $str        = str_replace( "\r", " ", $str );
            $str        = str_replace( "\n", " ", $str );
            // set the length of content since we have changed it.
            $this->size = strlen( $str );
        } //$stripRN
        $this->doc                        = $str;
        $this->pos                        = 0;
        $this->cursor                     = 1;
        $this->noise                      = array( );
        $this->nodes                      = array( );
        $this->lowercase                  = $lowercase;
        $this->default_br_text            = $defaultBRText;
        $this->default_span_text          = $defaultSpanText;
        $this->root                       = new simple_html_dom_node( $this );
        $this->root->tag                  = 'root';
        $this->root->_[ HDOM_INFO_BEGIN ] = -1;
        $this->root->nodetype             = HDOM_TYPE_ROOT;
        $this->parent                     = $this->root;
        if ( $this->size > 0 )
            $this->char = $this->doc[ 0 ];
    }
    // parse html content
    protected function parse( )
    {
        if ( ( $s = $this->copy_until_char( '<' ) ) === '' ) {
            return $this->read_tag();
        } //( $s = $this->copy_until_char( '<' ) ) === ''
        // text
        $node = new simple_html_dom_node( $this );
        ++$this->cursor;
        $node->_[ HDOM_INFO_TEXT ] = $s;
        $this->link_nodes( $node, false );
        return true;
    }
    // PAPERG - dkchou - added this to try to identify the character set of the page we have just parsed so we know better how to spit it out later.
    // NOTE:  IF you provide a routine called get_last_retrieve_url_contents_content_type which returns the CURLINFO_CONTENT_TYPE from the last curl_exec
    // (or the content_type header from the last transfer), we will parse THAT, and if a charset is specified, we will use it over any other mechanism.
    protected function parse_charset( )
    {
        global $debug_object;
        $charset = null;
        if ( function_exists( 'get_last_retrieve_url_contents_content_type' ) ) {
            $contentTypeHeader = get_last_retrieve_url_contents_content_type();
            $success           = preg_match( '/charset=(.+)/', $contentTypeHeader, $matches );
            if ( $success ) {
                $charset = $matches[ 1 ];
                if ( is_object( $debug_object ) ) {
                    $debug_object->debug_log( 2, 'header content-type found charset of: ' . $charset );
                } //is_object( $debug_object )
            } //$success
        } //function_exists( 'get_last_retrieve_url_contents_content_type' )
        if ( empty( $charset ) ) {
            $el = $this->root->find( 'meta[http-equiv=Content-Type]', 0, true );
            if ( !empty( $el ) ) {
                $fullvalue = $el->content;
                if ( is_object( $debug_object ) ) {
                    $debug_object->debug_log( 2, 'meta content-type tag found' . $fullvalue );
                } //is_object( $debug_object )
                if ( !empty( $fullvalue ) ) {
                    $success = preg_match( '/charset=(.+)/i', $fullvalue, $matches );
                    if ( $success ) {
                        $charset = $matches[ 1 ];
                    } //$success
                    else {
                        // If there is a meta tag, and they don't specify the character set, research says that it's typically ISO-8859-1
                        if ( is_object( $debug_object ) ) {
                            $debug_object->debug_log( 2, 'meta content-type tag couldn\'t be parsed. using iso-8859 default.' );
                        } //is_object( $debug_object )
                        $charset = 'ISO-8859-1';
                    }
                } //!empty( $fullvalue )
            } //!empty( $el )
        } //empty( $charset )
        // If we couldn't find a charset above, then lets try to detect one based on the text we got...
        if ( empty( $charset ) ) {
            // Use this in case mb_detect_charset isn't installed/loaded on this machine.
            $charset = false;
            if ( function_exists( 'mb_detect_encoding' ) ) {
                // Have php try to detect the encoding from the text given to us.
                $charset = mb_detect_encoding( $this->root->plaintext . "ascii", $encoding_list = array(
                     "UTF-8",
                    "CP1252"
                ) );
                if ( is_object( $debug_object ) ) {
                    $debug_object->debug_log( 2, 'mb_detect found: ' . $charset );
                } //is_object( $debug_object )
            } //function_exists( 'mb_detect_encoding' )
            // and if this doesn't work...  then we need to just wrongheadedly assume it's UTF-8 so that we can move on - cause this will usually give us most of what we need...
            if ( $charset === false ) {
                if ( is_object( $debug_object ) ) {
                    $debug_object->debug_log( 2, 'since mb_detect failed - using default of utf-8' );
                } //is_object( $debug_object )
                $charset = 'UTF-8';
            } //$charset === false
        } //empty( $charset )
        // Since CP1252 is a superset, if we get one of it's subsets, we want it instead.
        if ( ( strtolower( $charset ) == strtolower( 'ISO-8859-1' ) ) || ( strtolower( $charset ) == strtolower( 'Latin1' ) ) || ( strtolower( $charset ) == strtolower( 'Latin-1' ) ) ) {
            if ( is_object( $debug_object ) ) {
                $debug_object->debug_log( 2, 'replacing ' . $charset . ' with CP1252 as its a superset' );
            } //is_object( $debug_object )
            $charset = 'CP1252';
        } //( strtolower( $charset ) == strtolower( 'ISO-8859-1' ) ) || ( strtolower( $charset ) == strtolower( 'Latin1' ) ) || ( strtolower( $charset ) == strtolower( 'Latin-1' ) )
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log( 1, 'EXIT - ' . $charset );
        } //is_object( $debug_object )
        return $this->_charset = $charset;
    }
    // read tag info
    protected function read_tag( )
    {
        if ( $this->char !== '<' ) {
            $this->root->_[ HDOM_INFO_END ] = $this->cursor;
            return false;
        } //$this->char !== '<'
        $begin_tag_pos = $this->pos;
        $this->char    = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
        // end tag
        if ( $this->char === '/' ) {
            $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
            // This represents the change in the simple_html_dom trunk from revision 180 to 181.
            // $this->skip($this->token_blank_t);
            $this->skip( $this->token_blank );
            $tag = $this->copy_until_char( '>' );
            // skip attributes in end tag
            if ( ( $pos = strpos( $tag, ' ' ) ) !== false )
                $tag = substr( $tag, 0, $pos );
            $parent_lower = strtolower( $this->parent->tag );
            $tag_lower    = strtolower( $tag );
            if ( $parent_lower !== $tag_lower ) {
                if ( isset( $this->optional_closing_tags[ $parent_lower ] ) && isset( $this->block_tags[ $tag_lower ] ) ) {
                    $this->parent->_[ HDOM_INFO_END ] = 0;
                    $org_parent                       = $this->parent;
                    while ( ( $this->parent->parent ) && strtolower( $this->parent->tag ) !== $tag_lower )
                        $this->parent = $this->parent->parent;
                    if ( strtolower( $this->parent->tag ) !== $tag_lower ) {
                        $this->parent = $org_parent; // restore origonal parent
                        if ( $this->parent->parent )
                            $this->parent = $this->parent->parent;
                        $this->parent->_[ HDOM_INFO_END ] = $this->cursor;
                        return $this->as_text_node( $tag );
                    } //strtolower( $this->parent->tag ) !== $tag_lower
                } //isset( $this->optional_closing_tags[ $parent_lower ] ) && isset( $this->block_tags[ $tag_lower ] )
                else if ( ( $this->parent->parent ) && isset( $this->block_tags[ $tag_lower ] ) ) {
                    $this->parent->_[ HDOM_INFO_END ] = 0;
                    $org_parent                       = $this->parent;
                    while ( ( $this->parent->parent ) && strtolower( $this->parent->tag ) !== $tag_lower )
                        $this->parent = $this->parent->parent;
                    if ( strtolower( $this->parent->tag ) !== $tag_lower ) {
                        $this->parent                     = $org_parent; // restore origonal parent
                        $this->parent->_[ HDOM_INFO_END ] = $this->cursor;
                        return $this->as_text_node( $tag );
                    } //strtolower( $this->parent->tag ) !== $tag_lower
                } //( $this->parent->parent ) && isset( $this->block_tags[ $tag_lower ] )
                else if ( ( $this->parent->parent ) && strtolower( $this->parent->parent->tag ) === $tag_lower ) {
                    $this->parent->_[ HDOM_INFO_END ] = 0;
                    $this->parent                     = $this->parent->parent;
                } //( $this->parent->parent ) && strtolower( $this->parent->parent->tag ) === $tag_lower
                else
                    return $this->as_text_node( $tag );
            } //$parent_lower !== $tag_lower
            $this->parent->_[ HDOM_INFO_END ] = $this->cursor;
            if ( $this->parent->parent )
                $this->parent = $this->parent->parent;
            $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
            return true;
        } //$this->char === '/'
        $node                       = new simple_html_dom_node( $this );
        $node->_[ HDOM_INFO_BEGIN ] = $this->cursor;
        ++$this->cursor;
        $tag             = $this->copy_until( $this->token_slash );
        $node->tag_start = $begin_tag_pos;
        // doctype, cdata & comments...
        if ( isset( $tag[ 0 ] ) && $tag[ 0 ] === '!' ) {
            $node->_[ HDOM_INFO_TEXT ] = '<' . $tag . $this->copy_until_char( '>' );
            if ( isset( $tag[ 2 ] ) && $tag[ 1 ] === '-' && $tag[ 2 ] === '-' ) {
                $node->nodetype = HDOM_TYPE_COMMENT;
                $node->tag      = 'comment';
            } //isset( $tag[ 2 ] ) && $tag[ 1 ] === '-' && $tag[ 2 ] === '-'
            else {
                $node->nodetype = HDOM_TYPE_UNKNOWN;
                $node->tag      = 'unknown';
            }
            if ( $this->char === '>' )
                $node->_[ HDOM_INFO_TEXT ] .= '>';
            $this->link_nodes( $node, true );
            $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
            return true;
        } //isset( $tag[ 0 ] ) && $tag[ 0 ] === '!'
        // text
        if ( $pos = strpos( $tag, '<' ) !== false ) {
            $tag                       = '<' . substr( $tag, 0, -1 );
            $node->_[ HDOM_INFO_TEXT ] = $tag;
            $this->link_nodes( $node, false );
            $this->char = $this->doc[ --$this->pos ]; // prev
            return true;
        } //$pos = strpos( $tag, '<' ) !== false
        if ( !preg_match( "/^[\w-:]+$/", $tag ) ) {
            $node->_[ HDOM_INFO_TEXT ] = '<' . $tag . $this->copy_until( '<>' );
            if ( $this->char === '<' ) {
                $this->link_nodes( $node, false );
                return true;
            } //$this->char === '<'
            if ( $this->char === '>' )
                $node->_[ HDOM_INFO_TEXT ] .= '>';
            $this->link_nodes( $node, false );
            $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
            return true;
        } //!preg_match( "/^[\w-:]+$/", $tag )
        // begin tag
        $node->nodetype = HDOM_TYPE_ELEMENT;
        $tag_lower      = strtolower( $tag );
        $node->tag      = ( $this->lowercase ) ? $tag_lower : $tag;
        // handle optional closing tags
        if ( isset( $this->optional_closing_tags[ $tag_lower ] ) ) {
            while ( isset( $this->optional_closing_tags[ $tag_lower ][ strtolower( $this->parent->tag ) ] ) ) {
                $this->parent->_[ HDOM_INFO_END ] = 0;
                $this->parent                     = $this->parent->parent;
            } //isset( $this->optional_closing_tags[ $tag_lower ][ strtolower( $this->parent->tag ) ] )
            $node->parent = $this->parent;
        } //isset( $this->optional_closing_tags[ $tag_lower ] )
        $guard = 0; // prevent infinity loop
        $space = array(
             $this->copy_skip( $this->token_blank ),
            '',
            ''
        );
        // attributes
        do {
            if ( $this->char !== null && $space[ 0 ] === '' ) {
                break;
            } //$this->char !== null && $space[ 0 ] === ''
            $name = $this->copy_until( $this->token_equal );
            if ( $guard === $this->pos ) {
                $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
                continue;
            } //$guard === $this->pos
            $guard = $this->pos;
            // handle endless '<'
            if ( $this->pos >= $this->size - 1 && $this->char !== '>' ) {
                $node->nodetype            = HDOM_TYPE_TEXT;
                $node->_[ HDOM_INFO_END ]  = 0;
                $node->_[ HDOM_INFO_TEXT ] = '<' . $tag . $space[ 0 ] . $name;
                $node->tag                 = 'text';
                $this->link_nodes( $node, false );
                return true;
            } //$this->pos >= $this->size - 1 && $this->char !== '>'
            // handle mismatch '<'
            if ( $this->doc[ $this->pos - 1 ] == '<' ) {
                $node->nodetype            = HDOM_TYPE_TEXT;
                $node->tag                 = 'text';
                $node->attr                = array( );
                $node->_[ HDOM_INFO_END ]  = 0;
                $node->_[ HDOM_INFO_TEXT ] = substr( $this->doc, $begin_tag_pos, $this->pos - $begin_tag_pos - 1 );
                $this->pos -= 2;
                $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
                $this->link_nodes( $node, false );
                return true;
            } //$this->doc[ $this->pos - 1 ] == '<'
            if ( $name !== '/' && $name !== '' ) {
                $space[ 1 ] = $this->copy_skip( $this->token_blank );
                $name       = $this->restore_noise( $name );
                if ( $this->lowercase )
                    $name = strtolower( $name );
                if ( $this->char === '=' ) {
                    $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
                    $this->parse_attr( $node, $name, $space );
                } //$this->char === '='
                else {
                    //no value attr: nowrap, checked selected...
                    $node->_[ HDOM_INFO_QUOTE ][ ] = HDOM_QUOTE_NO;
                    $node->attr[ $name ]           = true;
                    if ( $this->char != '>' )
                        $this->char = $this->doc[ --$this->pos ]; // prev
                }
                $node->_[ HDOM_INFO_SPACE ][ ] = $space;
                $space                         = array(
                     $this->copy_skip( $this->token_blank ),
                    '',
                    ''
                );
            } //$name !== '/' && $name !== ''
            else
                break;
        } while ( $this->char !== '>' && $this->char !== '/' );
        $this->link_nodes( $node, true );
        $node->_[ HDOM_INFO_ENDSPACE ] = $space[ 0 ];
        // check self closing
        if ( $this->copy_until_char_escape( '>' ) === '/' ) {
            $node->_[ HDOM_INFO_ENDSPACE ] .= '/';
            $node->_[ HDOM_INFO_END ] = 0;
        } //$this->copy_until_char_escape( '>' ) === '/'
        else {
            // reset parent
            if ( !isset( $this->self_closing_tags[ strtolower( $node->tag ) ] ) )
                $this->parent = $node;
        }
        $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
        // If it's a BR tag, we need to set it's text to the default text.
        // This way when we see it in plaintext, we can generate formatting that the user wants.
        // since a br tag never has sub nodes, this works well.
        if ( $node->tag == "br" ) {
            $node->_[ HDOM_INFO_INNER ] = $this->default_br_text;
        } //$node->tag == "br"
        return true;
    }
    // parse attributes
    protected function parse_attr( $node, $name, &$space )
    {
        // Per sourceforge: http://sourceforge.net/tracker/?func=detail&aid=3061408&group_id=218559&atid=1044037
        // If the attribute is already defined inside a tag, only pay atetntion to the first one as opposed to the last one.
        if ( isset( $node->attr[ $name ] ) ) {
            return;
        } //isset( $node->attr[ $name ] )
        $space[ 2 ] = $this->copy_skip( $this->token_blank );
        switch ( $this->char ) {
            case '"':
                $node->_[ HDOM_INFO_QUOTE ][ ] = HDOM_QUOTE_DOUBLE;
                $this->char                    = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
                $node->attr[ $name ]           = $this->restore_noise( $this->copy_until_char_escape( '"' ) );
                $this->char                    = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
                break;
            case '\'':
                $node->_[ HDOM_INFO_QUOTE ][ ] = HDOM_QUOTE_SINGLE;
                $this->char                    = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
                $node->attr[ $name ]           = $this->restore_noise( $this->copy_until_char_escape( '\'' ) );
                $this->char                    = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
                break;
            default:
                $node->_[ HDOM_INFO_QUOTE ][ ] = HDOM_QUOTE_NO;
                $node->attr[ $name ]           = $this->restore_noise( $this->copy_until( $this->token_attr ) );
        } //$this->char
        // PaperG: Attributes should not have \r or \n in them, that counts as html whitespace.
        $node->attr[ $name ] = str_replace( "\r", "", $node->attr[ $name ] );
        $node->attr[ $name ] = str_replace( "\n", "", $node->attr[ $name ] );
        // PaperG: If this is a "class" selector, lets get rid of the preceeding and trailing space since some people leave it in the multi class case.
        if ( $name == "class" ) {
            $node->attr[ $name ] = trim( $node->attr[ $name ] );
        } //$name == "class"
    }
    // link node's parent
    protected function link_nodes( &$node, $is_child )
    {
        $node->parent           = $this->parent;
        $this->parent->nodes[ ] = $node;
        if ( $is_child ) {
            $this->parent->children[ ] = $node;
        } //$is_child
    }
    // as a text node
    protected function as_text_node( $tag )
    {
        $node = new simple_html_dom_node( $this );
        ++$this->cursor;
        $node->_[ HDOM_INFO_TEXT ] = '</' . $tag . '>';
        $this->link_nodes( $node, false );
        $this->char = ( ++$this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
        return true;
    }
    protected function skip( $chars )
    {
        $this->pos += strspn( $this->doc, $chars, $this->pos );
        $this->char = ( $this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
    }
    protected function copy_skip( $chars )
    {
        $pos = $this->pos;
        $len = strspn( $this->doc, $chars, $pos );
        $this->pos += $len;
        $this->char = ( $this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
        if ( $len === 0 )
            return '';
        return substr( $this->doc, $pos, $len );
    }
    protected function copy_until( $chars )
    {
        $pos = $this->pos;
        $len = strcspn( $this->doc, $chars, $pos );
        $this->pos += $len;
        $this->char = ( $this->pos < $this->size ) ? $this->doc[ $this->pos ] : null; // next
        return substr( $this->doc, $pos, $len );
    }
    protected function copy_until_char( $char )
    {
        if ( $this->char === null )
            return '';
        if ( ( $pos = strpos( $this->doc, $char, $this->pos ) ) === false ) {
            $ret        = substr( $this->doc, $this->pos, $this->size - $this->pos );
            $this->char = null;
            $this->pos  = $this->size;
            return $ret;
        } //( $pos = strpos( $this->doc, $char, $this->pos ) ) === false
        if ( $pos === $this->pos )
            return '';
        $pos_old    = $this->pos;
        $this->char = $this->doc[ $pos ];
        $this->pos  = $pos;
        return substr( $this->doc, $pos_old, $pos - $pos_old );
    }
    protected function copy_until_char_escape( $char )
    {
        if ( $this->char === null )
            return '';
        $start = $this->pos;
        while ( 1 ) {
            if ( ( $pos = strpos( $this->doc, $char, $start ) ) === false ) {
                $ret        = substr( $this->doc, $this->pos, $this->size - $this->pos );
                $this->char = null;
                $this->pos  = $this->size;
                return $ret;
            } //( $pos = strpos( $this->doc, $char, $start ) ) === false
            if ( $pos === $this->pos )
                return '';
            if ( $this->doc[ $pos - 1 ] === '\\' ) {
                $start = $pos + 1;
                continue;
            } //$this->doc[ $pos - 1 ] === '\\'
            $pos_old    = $this->pos;
            $this->char = $this->doc[ $pos ];
            $this->pos  = $pos;
            return substr( $this->doc, $pos_old, $pos - $pos_old );
        } //1
    }
    // remove noise from html content
    // save the noise in the $this->noise array.
    protected function remove_noise( $pattern, $remove_tag = false )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        $count = preg_match_all( $pattern, $this->doc, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );
        for ( $i = $count - 1; $i > -1; --$i ) {
            $key = '___noise___' . sprintf( '% 5d', count( $this->noise ) + 1000 );
            if ( is_object( $debug_object ) ) {
                $debug_object->debug_log( 2, 'key is: ' . $key );
            } //is_object( $debug_object )
            $idx                 = ( $remove_tag ) ? 0 : 1;
            $this->noise[ $key ] = $matches[ $i ][ $idx ][ 0 ];
            $this->doc           = substr_replace( $this->doc, $key, $matches[ $i ][ $idx ][ 1 ], strlen( $matches[ $i ][ $idx ][ 0 ] ) );
        } //$i = $count - 1; $i > -1; --$i
        // reset the length of content
        $this->size = strlen( $this->doc );
        if ( $this->size > 0 ) {
            $this->char = $this->doc[ 0 ];
        } //$this->size > 0
    }
    // restore noise to html content
    function restore_noise( $text )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        while ( ( $pos = strpos( $text, '___noise___' ) ) !== false ) {
            // Sometimes there is a broken piece of markup, and we don't GET the pos+11 etc... token which indicates a problem outside of us...
            if ( strlen( $text ) > $pos + 15 ) {
                $key = '___noise___' . $text[ $pos + 11 ] . $text[ $pos + 12 ] . $text[ $pos + 13 ] . $text[ $pos + 14 ] . $text[ $pos + 15 ];
                if ( is_object( $debug_object ) ) {
                    $debug_object->debug_log( 2, 'located key of: ' . $key );
                } //is_object( $debug_object )
                if ( isset( $this->noise[ $key ] ) ) {
                    $text = substr( $text, 0, $pos ) . $this->noise[ $key ] . substr( $text, $pos + 16 );
                } //isset( $this->noise[ $key ] )
                else {
                    // do this to prevent an infinite loop.
                    $text = substr( $text, 0, $pos ) . 'UNDEFINED NOISE FOR KEY: ' . $key . substr( $text, $pos + 16 );
                }
            } //strlen( $text ) > $pos + 15
            else {
                // There is no valid key being given back to us... We must get rid of the ___noise___ or we will have a problem.
                $text = substr( $text, 0, $pos ) . 'NO NUMERIC NOISE KEY' . substr( $text, $pos + 11 );
            }
        } //( $pos = strpos( $text, '___noise___' ) ) !== false
        return $text;
    }
    // Sometimes we NEED one of the noise elements.
    function search_noise( $text )
    {
        global $debug_object;
        if ( is_object( $debug_object ) ) {
            $debug_object->debug_log_entry( 1 );
        } //is_object( $debug_object )
        foreach ( $this->noise as $noiseElement ) {
            if ( strpos( $noiseElement, $text ) !== false ) {
                return $noiseElement;
            } //strpos( $noiseElement, $text ) !== false
        } //$this->noise as $noiseElement
    }
    function __toString( )
    {
        return $this->root->innertext();
    }
    function __get( $name )
    {
        switch ( $name ) {
            case 'outertext':
                return $this->root->innertext();
            case 'innertext':
                return $this->root->innertext();
            case 'plaintext':
                return $this->root->text();
            case 'charset':
                return $this->_charset;
            case 'target_charset':
                return $this->_target_charset;
        } //$name
    }
    // camel naming conventions
    function childNodes( $idx = -1 )
    {
        return $this->root->childNodes( $idx );
    }
    function firstChild( )
    {
        return $this->root->first_child();
    }
    function lastChild( )
    {
        return $this->root->last_child();
    }
    function createElement( $name, $value = null )
    {
        return @str_get_html( "<$name>$value</$name>" )->first_child();
    }
    function createTextNode( $value )
    {
        return @end( str_get_html( $value )->nodes );
    }
    function getElementById( $id )
    {
        return $this->find( "#$id", 0 );
    }
    function getElementsById( $id, $idx = null )
    {
        return $this->find( "#$id", $idx );
    }
    function getElementByTagName( $name )
    {
        return $this->find( $name, 0 );
    }
    function getElementsByTagName( $name, $idx = -1 )
    {
        return $this->find( $name, $idx );
    }
    function loadFile( )
    {
        $args = func_get_args();
        $this->load_file( $args );
    }
}
