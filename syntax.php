<?php
/**
 * RSS Syntax Plugin: display feeds in your wiki.
 *
 * @author Algorys
 */

if (!defined('DOKU_INC')) die();

class syntax_plugin_rss extends DokuWiki_Syntax_Plugin {

    public function getType() {
        return 'container';
    }

    /**
    * @return string Paragraph type
    **/

    public function getPType() {
        return 'normal';
    }

    // Keep syntax inside plugin
    function getAllowedTypes() {
	    return array('container', 'baseonly', 'substition','protected','disabled','formatting','paragraphs');
    }

    public function getSort() {
        return 198;
    }

    function connectTo($mode) {
            $this->Lexer->addSpecialPattern('<rss[^>]*>(?=.*)', $mode,'plugin_rss');
    }
    

/**
* Do the regexp
**/
    function handle($match, $state, $pos, Doku_Handler $handler) {
        switch($state){
            case DOKU_LEXER_SPECIAL :
            case DOKU_LEXER_ENTER :
                $data = array(
                        'state'=>$state,
                        'feed'=> "",
                    );
                // Looking for id
                preg_match("/feed *= *(['\"])(.*?)\\1/", $match, $feed);
                if( count($feed) != 0 ) {
                    $data['feed'] = $feed[2];
                }
                return $data;           
            case DOKU_LEXER_UNMATCHED :
                return array('state'=>$state, 'text'=>$match);
            default:
                return array('state'=>$state, 'bytepos_end' => $pos + strlen($match));
         }
    }

    function _check_rss($data) {
        $feed = $data['feed'];
        try {
            if(!@$fluxrss=simplexml_load_file($feed)) {
                throw new Exception('Flux invalide');
            }
            if(empty($fluxrss->channel->title) || empty($fluxrss->channel->description) || empty($fluxrss->channel->item->title)) {
                throw new Exception('Invalid Feed !');
            }
            //$renderer->doc .= '<p>Flux RSS trouvé !</p>';
        } catch(Exception $e){
            echo $e->getMessage();
        }
        return $fluxrss;
    }

    function _render_rss($renderer, $data){
        $fluxrss = $this->_check_rss($data);
        if($fluxrss) {
            $renderer->doc .= '<img src="lib/plugins/rss/images/rss.png" alt="rss" class="rss">';
            $renderer->doc .= '<h3>'.(string)$fluxrss->channel->title.'</h3>';
            $renderer->doc .= '<p>'.(string)$fluxrss->channel->description.'</p>';
            $i = 0;
            $nb_to_display = 5;
            $renderer->doc .= '<ul>';
            foreach($fluxrss->channel->item as $item) {
                $renderer->doc .= '<li>';
                $renderer->doc .= '<a href="'.(string)$item->link.'">'.(string)$item->title.'</a>';
                $renderer->doc .= '<i> &#9998 publié le '.(string)date('d/m/Y à G\hi',strtotime($item->pubDate)).'</i>';
                $renderer->doc .= '</li>';
                if(++$i>=$nb_to_display)
                    break;
            }
        }else {
            $renderer->doc .= '<p>Le Flux est vide ! </p>';
        }
    }

    // Dokuwiki Renderer
    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode != 'xhtml') return false;

        if($data['error']) {
            $renderer->doc .= $data['text'];
            return true;
        }
        $renderer->info['cache'] = false;
        switch($data['state']) {
            case DOKU_LEXER_SPECIAL :
            case DOKU_LEXER_ENTER :
                $this->_render_rss($renderer, $data);
                break;
            case DOKU_LEXER_UNMATCHED :
                $renderer->doc .= $renderer->_xmlEntities($data['text']);
                break;
        }
        return true;
    }

}
