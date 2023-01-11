<?php
/*
vim: set expandtab sw=4 ts=4 sts=4 foldmethod=indent:
Plugin Name: BSTabs 
Description: WP Extension for simple management of tabulatures
Version: 1.12
Author: Michal Nezerka 
Author URI: http://blue.pavoucek.cz
Text Domain: bstabs 
Domain Path: /languages
*/

// Require additional code 
require_once('bsmetabox.php');

// Chained project: http://www.appelsiini.net/projects/chained

class BSTabs
{  
    protected $pluginPath;
    protected $pluginUrl;

    static public $CAP_ADD = 'add_tabs';
    static public $CAP_EDIT = 'edit_tabs';
    static public $CAP_MANAGE = 'manage_tabs';
    static public $TAB_FIELDS =         array('title', 'instrument', 'author', 'key', 'level', 'genre', 'tuning', 'audio', 'tabs', 'links', 'published');
    static public $TAB_FIELDS_DEFAULT = array('title', 'instrument', 'author', 'key', 'level', 'genre', 'tuning', 'audio', 'tabs', 'links');


    static public $mimeTypesTab = array(
        'pdf' => 'application/pdf',
        'gif' => 'image/gif',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'zip' => 'application/zip',
        'gpx' => 'application/gpx',
        'tab' => 'application/tab',
        'gp' => 'application/gp',
        'gp3' => 'application/gp3',
        'gp4' => 'application/gp4',
        'gp5' => 'application/gp5',
        'gtp' => 'application/gtp',
        'tef' => 'application/tef',
        'tab' => 'application/tab',
        'btab' => 'application/btab',
        'tg' => 'application/tg',
        'tbl' => 'application/tbl',
        'mscz' => 'application/mscz'
        );

    static public $mimeTypesAudio = array(
        'mp3' => 'audio/mpeg3',
        'mpeg' => 'audio/mpeg',
        'mid|midi' => 'audio/midi',
        'ra|ram' => 'audio/x-realaudio',
        'wav' => 'audio/wav',
        'ogg|oga' => 'audio/ogg',
        'wma' => 'audio/x-ms-wma');

    public function __construct()  
    {  
        $this->pluginPath = plugin_dir_path(__FILE__);
        $this->pluginUrl = plugin_dir_url(__FILE__);

        add_action('init', array($this, 'onInit'));
        add_action('plugins_loaded', array($this, 'onPluginsLoaded'));

        // farm columns in admin listing ----------------------
        add_filter('manage_edit-bstab_columns', array($this, 'onAddTabsListingColumns'));
        add_action('manage_posts_custom_column', array($this, 'onShowListingCustomColumn'));

        add_filter('members_get_capabilities', array($this, 'onGetCapabilities'));
        add_filter('wp_get_attachment_url', array($this, 'onWpGetAttachmentUrl'));

   
        add_shortcode('tabs', array($this, 'onShortcodeTabs'));

        // include scripts and stylesheets -----------------------------
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-autocomplete');

        /*
        if (!is_admin())
        {
            //wp_enqueue_script('chained-min-js', plugins_url('/js/jquery.chained.min.js', __FILE__), array('jquery', 'jquery-ui-core'));
            wp_enqueue_style('bstabs-css', plugins_url('/css/bstabs.css', __FILE__));
        }
        */

        add_filter('upload_mimes', array($this, 'onUploadMimeTypes'));
        add_filter('the_content', array($this, 'onContent'));
    }

    public function onInit()
    {
        // register new post type for farms
        register_post_type('bstab', array(
            'labels' => array(
                'name' => __('Tabs', 'bstabs'),
                'singular_name' => __('Tab', 'bstabs'),
                'add_new' => __('Add new', 'bstabs'),
                'add_new_item' => __('Add new tab', 'bstabs'),
                'edit_item' => __('Edit tab', 'bstabs'),
                'new_item' => __('New tab', 'bstabs'),
                'view_item' => __('View tab', 'bstabs'),
                'search_items' => __('Search for tab', 'bstabs'),
                'not_found' => __('No tabs were found', 'bstabs')),
            'public' => true,
            //'menu_icon' => $this->pluginUrl . '/images/farms-icon.png',
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'capability_type' => 'tab',
            'exclude_from_search' => true,
            'capabilities' => array(
                'publish_posts' => 'publish_tabs',
                'edit_posts' => 'edit_tabs',
                'edit_others_posts' => 'edit_others_tabs',
                'delete_posts' => 'delete_tabs',
                'delete_others_posts' => 'delete_others_tabs',
                'read_private_posts' => 'read_private_tabs',
                'edit_post' => 'edit_tab',
                'delete_post' => 'delete_tab',
                'read_post' => 'read_tab')));

        // tab metabox ----------------------------------------------- 
        $levels = array('-' => '-');
        foreach ($this->getLevels() as $level => $levelData)
            $levels[$level] = $levelData['label'];
        $genres = array_merge(array('-' => '-'), $this->getGenres());
        $keys = array_merge(array('-' => '-'), $this->getKeys());

        $metaBox = array(
            'id' => 'tab-meta-box',
            'title' => __('Tabulature information', 'bstabs'),
            'context' => 'normal',
            'priority' => 'high',
            'post_types' => array('bstab'),
            'fields' => array(
                array(
                    'name' => __('Instrument', 'bstabs'),
                    'id' => 'bstabs_instrument',
                    'type' => 'select',
                    'options' => $this->getInstruments()),
                array(
                    'name' => __('Level', 'bstabs'),
                    'id' => 'bstabs_level',
                    'type' => 'select',
                    'options' => $levels),
                array(
                    'name' => __('Genre', 'bstabs'),
                    'id' => 'bstabs_genre',
                    'type' => 'select',
                    'options' => $genres),
                array(
                    'name' => __('Key', 'bstabs'),
                    'id' => 'bstabs_key',
                    'type' => 'select',
                    'options' => $keys),
                array(
                    'name' => __('Author', 'bstabs'),
                    'id' => 'bstabs_author',
                    'type' => 'text',
                    'hints' => array_keys($this->getAuthors())),
                array(
                    'name' => __('Tuning', 'bstabs'),
                    'id' => 'bstabs_tuning',
                    'type' => 'text',
                    'hints' => $this->getTunings()),
                array(
                    'name' => __('Links', 'bstabs'),
                    'id' => 'bstabs_links',
                    'type' => 'textarea',
                    'help' => __('List of links, where each line should have following format: "http://something.somedomain; Some description"'))
            ));

        new BSMetaBox($metaBox);
    }

    public function onGetCapabilities($capabilities)
    {
        $capabilities[] = 'read_tab';
        $capabilities[] = 'edit_tab';
        $capabilities[] = 'delete_tab';
        $capabilities[] = 'publish_tabs';
        $capabilities[] = 'edit_tabs';
        $capabilities[] = 'edit_others_tabs';
        $capabilities[] = 'delete_tabs';
        $capabilities[] = 'delete_others_tabs';
        $capabilities[] = BSTabs::$CAP_ADD;
        $capabilities[] = BSTabs::$CAP_EDIT;
        $capabilities[] = BSTabs::$CAP_MANAGE;

        return $capabilities;
    }

    public function onWpGetAttachmentUrl($url)
    {
        // escape bad characters in file name (url must be split and joined to avoid escaping slashes etc.)
        $result = path_join(dirname($url), rawurlencode(basename($url)));
        return $result;
    }

    // set text domain for i18n
    public function onPluginsLoaded()
    {
        load_plugin_textdomain('bstabs', false, 'bstabs/languages');
    }

    // add necessary mime types to enable upload of tabs and related stuff
    public function onUploadMimeTypes($mimeTypes = array())
    {
        foreach (BSTabs::$mimeTypesTab as $ext => $mimeType)
            if (!isset($mimeTypes[$ext]))
                $mimeTypes[$ext] = $mimeType;
        
        foreach (BSTabs::$mimeTypesAudio as $ext => $mimeType)
            if (!isset($mimeTypes[$ext]))
                $mimeTypes[$ext] = $mimeType;
         
       
        return $mimeTypes;
    }

    public function onContent($content)
    {
        global $post;

        // modify content only of bstab post types
        if ($post->post_type != 'bstab')
            return $content;

        $contentTabs = '';

        // show tab metadata
        $custom = get_post_custom();

        // prepare tabs and audio
        $files = $this->getFiles();

        // prepare list of links
        $links = $this->getLinks($custom['bstabs_links'][0]);

        $contentTabs .= '<table class="bstab-details">';

        $contentTabs .= '  <tr><td class="label">' . __('Instrument', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $this->getValueForKey($custom['bstabs_instrument'][0], BSTabs::getInstruments()) . '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Author', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $custom['bstabs_author'][0] .  '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Key', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $this->getValueForKey($custom['bstabs_key'][0], $this->getKeys()) . '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Level', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $this->getValueForKey($custom['bstabs_level'][0], BSTabs::getLevels()) . '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Genre', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $this->getValueForKey($custom['bstabs_genre'][0], BSTabs::getGenres()) . '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Tuning', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $custom['bstabs_tuning'][0] . '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Tabs', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $this->formatFiles($files, BSTabs::$mimeTypesTab, 'list') . '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Audio', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $this->formatFiles($files, BSTabs::$mimeTypesAudio, 'list') . '</td></tr>';

        $contentTabs .= '  <tr><td class="label">' . __('Links', 'bstabs') . ':</td>';
        $contentTabs .= '     <td class="value">' . $this->formatLinks($links, 'list') . '</td></tr>';

        $contentTabs .= '</table>';

        // show tab attachments

        $content .= $contentTabs;

        return $content;
    }

    public function onAddTabsListingColumns($cols)
    {
        $cols['bstabs_instrument'] = __('Instrument', 'bstabs');
        $cols['bstabs_author'] = __('Author', 'bstabs');
        $cols['bstabs_level'] = __('Level', 'bstabs');
        $cols['bstabs_key'] = __('Key', 'bstabs');
        $cols['bstabs_tabs'] = __('Tabs', 'bstabs');
        $cols['bstabs_audio'] = __('Audio', 'bstabs');
        $cols['bstabs_links'] = __('Links', 'bstabs');

        return $cols;
    }

    public function onShowListingCustomColumn($column)
    {
        global $post;

        $custom = get_post_custom();

        switch ($column)
        {
            // farm fields
            case 'bstabs_instrument':
                $instruments = $this->getInstruments();
                $instrument = $custom['bstabs_instrument'][0];
                echo isset($instruments[$instrument]) ? $instruments[$instrument] : 'n/a';
                break;
            case 'bstabs_key':
                $keys = $this->getKeys();
                $key = $custom['bstabs_key'][0];
                echo isset($keys[$key]) ? $keys[$key] : '-';
                break;
            case 'bstabs_author':
                echo $custom['bstabs_author'][0];
                break;
            case 'bstabs_level':
                $levels = $this->getLevels();
                $levelId = $custom['bstabs_level'][0];
                echo isset($levels[$levelId]) ? $levels[$levelId]['label'] : '-';
                break;
            case 'bstabs_tabs':
                $files = $this->getFiles();
                echo $this->formatFiles($files, BSTabs::$mimeTypesTab);
                break;
            case 'bstabs_audio':
                $files = $this->getFiles();
                echo $this->formatFiles($files, BSTabs::$mimeTypesAudio);
                break;
            case 'bstabs_links':
                $links = $this->getLinks($custom['bstabs_links'][0]);
                echo $this->formatLinks($links);
                break;
        }
    }

    private function getTabsListHeader($atts, $fields)
    {
        $result = '<tr>';
        foreach ($fields as $f)
        {
            $result .= '  <th>';

            switch ($f) 
            {
                case 'title':
                    $result .= __('Tune', 'bstabs');
                    break;
                case 'instrument':
                    if ($atts['instrument'] === 'all')
                        $result .=  __('Instrument', 'bstabs');
                    break;
                case 'author':
                    $result .= __('Author', 'bstabs');
                    break;
                case 'key':
                    $result .= __('Key', 'bstabs');
                    break;
                case 'level':
                    $result .= __('Level', 'bstabs');
                    break;
                case 'genre':
                    $result .= __('Genre', 'bstabs');
                    break;
                case 'tuning':
                    $result .= __('Tuning', 'bstabs');
                    break;
                case 'tabs':
                    $result .= __('Tabs', 'bstabs');
                    break;
                case 'audio':
                    $result .= __('Audio', 'bstabs');
                    break;
                case 'links':
                    $result .= __('Links', 'bstabs');
                    break;
                case 'published':
                    $result .= __('Published', 'bstabs');
                    break;
                default:
                    $result .= '';
            }
            $result .= '  </th>';

        }
        $result .= '</tr>';

        return $result;
    }

    private function getTabsListItem($atts, $fields)
    {
        global $post;

        $result = '  <tr>';

        $custom = get_post_custom();

        // prepare tabs
        $files = $this->getFiles();

        foreach ($fields as $f)
        {
            $result .= '    <td>';
            switch ($f) 
            {
                case 'title':
                    //get_permalink($post->id)
                    //$result .= '<a href="' . $post->guid . '">' . $post->post_title . '</a>';
                    $result .= '<a href="' . get_permalink($post->id) . '">' . $post->post_title . '</a>';
                    break;
                case 'instrument':
                    if ($atts['instrument'] === 'all')
                        $result .= $this->getValueForKey($custom['bstabs_instrument'][0], $this->getInstruments());
                    break;
                case 'author':
                    $result .= $custom['bstabs_author'][0];
                    break;
                case 'key':
                    $result .= $this->getValueForKey($custom['bstabs_key'][0], $this->getKeys());
                    break;
                case 'level':
                    $result .= $this->getValueForKey($custom['bstabs_level'][0], $this->getLevels());
                    break;
                case 'genre':
                    $result .= $this->getValueForKey($custom['bstabs_genre'][0], $this->getGenres());
                    break;
                case 'tuning':
                    $result .= $custom['bstabs_tuning'][0];
                    break;
                case 'tabs':
                    $result .= $this->formatFiles($files, BSTabs::$mimeTypesTab);
                    break;
                case 'audio':
                    $result .= $this->formatFiles($files, BSTabs::$mimeTypesAudio);
                    break;
                case 'links':
                    // prepare links
                    $links = $this->getLinks($custom['bstabs_links'][0]);
                    $result .= $this->formatLinks($links);
                    break;
                case 'published':
                    $result .= get_the_date(); //$post->post_date;  
                    break;
                default:
                    $result .= '';
            }
            $result .= '</td>' . "\n";
        }

        $result .= '  </tr>';

        return $result;
    }

    public function onShortcodeTabs($atts)
    {
        global $post;

        // convert short code attributes to array with default values
        $atts = shortcode_atts(array(
            'search' => 'no',
            'name' => NULL,
            'post' => 'no',
            'instrument' => 'all',
            'search_all_button' => 'no',
            'count' => 0,
            'orderby' => NULL, 
            'order' => NULL,
            'fields' => NULL,
            'header' => true,
            'class' => NULL
        ), $atts, 'tabs');

        // check input values
        // - orderby
        if (!is_null($atts['orderby']))
            if (!in_array($atts['orderby'], array('author', 'title', 'published')))
               $atts['orderby'] = NULL;
        // - order
        if (!is_null($atts['order']))
            if (!in_array($atts['order'], array('asc', 'desc')))
               $atts['order'] = NULL;
        // - header
        if (!is_bool($atts['header']))
            if ($atts['header'] == 'no')
                $atts['header'] = false;
            else
                $atts['header'] = true;
        // - fields
        $showFields = BSTABS::$TAB_FIELDS_DEFAULT;
        if (!is_null($atts['fields']))
        {
            $fieldsParts = split(';', trim($atts['fields']));
            $fields = array();
            foreach ($fieldsParts as $f) 
                if (in_array($f, BSTABS::$TAB_FIELDS))
                    $fields[] = $f;
            if (count($fields) > 0)
                $showFields = $fields;
        }

        // form values
        $formName = '';
        $formInstrument = 'all';
        $formGenre = 'all';
        $formLevel = 'all';
        $formKey = 'all';
        $formAuthor = '';

        // search values to be used for WP queries (both search form and attributes)
        $sResults = '';
        $sName = is_null($atts['name']) ? '' : $atts['name'];
        $sInstrument = $atts['instrument'];
        $sGenre = 'all';
        $sLevel = 'all';
        $sKey = 'all';
        $sAuthor = '';

        $searchMode = $atts['search'] == 'yes';
        $showTabs = !$searchMode;

        // check if form was submitted
        if ($searchMode and (isset($_POST['bstabs_submit_button_filter']) or isset($_POST['bstabs_submit_button_all'])))
        {
            // process all search form fields
            $formName = sanitize_text_field($_POST['bstabs_name']);
            $formGenre = sanitize_text_field($_POST['bstabs_genre']);
            $formLevel = sanitize_text_field($_POST['bstabs_level']);
            $formKey = sanitize_text_field($_POST['bstabs_key']);
            $formAuthor = sanitize_text_field(base64_decode($_POST['bstabs_author']));

            $showTabs = true;
        }

        // check if form values should be used as filtering parameters 
        if ($searchMode and (isset($_POST['bstabs_submit_button_filter'])))
        {
            if ($atts['instrument'] === 'all')
                $sInstrument = sanitize_text_field($_POST['bstabs_instrument']);
            $sName = $formName;
            $sGenre = $formGenre;
            $sLevel = $formLevel;
            $sKey = $formKey;
            $sAuthor = $formAuthor;
        }

        // look for tabs
        if ($showTabs)
        {
            $args = array('post_type' => 'bstab', 'nopaging' => true);
            $argsMeta = array();
            if (strlen($sName) > 0)
                $args['s'] = $sName;
            if ($sInstrument !== 'all')
                $argsMeta[] = array('key' => 'bstabs_instrument', 'value' => $sInstrument);
            if (strlen($sGenre) > 0 && 'all' != $sGenre)
                $argsMeta[] = array('key' => 'bstabs_genre', 'value' => $sGenre);
            if (strlen($sLevel) > 0 && 'all' != $sLevel)
                $argsMeta[] = array('key' => 'bstabs_level', 'value' => $sLevel);
            if (strlen($sKey) > 0 && 'all' != $sKey)
                $argsMeta[] = array('key' => 'bstabs_key', 'value' => $sKey);
            if (strlen($sAuthor) > 0)
                $argsMeta[] = array('key' => 'bstabs_author', 'value' => $sAuthor, 'compare' => 'LIKE');

            if (count($argsMeta) > 0)
                $args['meta_query'] = $argsMeta; 

            $args['orderby'] = is_null($atts['orderby']) ? 'title' : $atts['orderby'];
            $args['order'] = is_null($atts['order']) ? 'asc' : $atts['order'];

            $loop = new WP_Query($args);
            if ($loop->have_posts())
            {
                $cssClass = 'bstabs';
                if (!is_null($atts['class']))
                    $cssClass .= ' ' . sanitize_text_field(trim($atts['class']));
                $sResults .= '<table class="' . $cssClass . '">';

                if ($atts['header'])
                    $sResults .= $this->getTabsListHeader($atts, $showFields);
                $postIx = 0;
                while ($loop->have_posts())
                {
                    if ($atts['count'] > 0 && $postIx >= $atts['count'])
                        break;
                    $loop->the_post();

                    $sResults .= $this->getTabsListItem($atts, $showFields);
                    $postIx += 1;
                }
                $sResults .= '</table>';
            }
            else
            {
                $sResults .= '<p>' . __('No tabs were found', 'bstabs') . '</p>'; 

            }
            wp_reset_postdata();
        }

        $result = '';

        // generate search form
        if ($searchMode)
        {
            $result .= '<form id="tabs-search" method="post">';
            $result .= '<div class="bstabs-search">';
            $result .= '  <div class="att">';
            $result .= '    <label for="bstabs_name">' . __('Name', 'bstabs') . ':</label>';
            $result .= '    <input type="text" name="bstabs_name" value="' . $formName . '" />';
            $result .= '  </div>';

            // instrument search field
            if ($atts['instrument'] === 'all')
            {
                $result .= '  <div class="att">';
                $result .= '    <label for="bstabs_instrument">' . __('Instrument', 'bstabs') . ':</label>';
                $result .= '    <select id="bstabs_instrument" name="bstabs_instrument"><option value="all">' . __('All instruments', 'bstabs') . '</option>';
                foreach ($this->getInstruments() as $key => $value)
                {
                    $checked = $key == $formInstrument ? 'selected="selected"' : '';
                    $result .= '<option ' . $checked . ' value="' . $key . '">' . $value . '</option>';
                }
                $result .= '    </select>';
                $result .= '  </div>';
            }

            // genre search field
            $result .= '  <div class="att">';
            $result .= '    <label for="bstabs_genre">' . __('Genre', 'bstabs') . ':</label>';
            $result .= '    <select name="bstabs_genre"><option value="all">' . __('All genres', 'bstabs') . '</option>';
            foreach ($this->getGenres($sInstrument, true) as $key => $value)
            {
                $checked = $key == $formGenre ? 'selected="selected"' : '';
                $result .= '<option ' . $checked . ' value="' . $key . '">' . $value . '</option>';
            }
            $result .= '    </select>';
            $result .= '  </div>';
            //$result .= '<script charset="utf-8">jQuery(function(jQuery) { jQuery("#bstabs_genre").chained("#bstabs_instrument"); });</script>';

            // level search field
            $result .= '  <div class="att">';
            $result .= '    <label for="bstabs_level">' . __('Level', 'bstabs') . ':</label>';
            $result .= '    <select id="bstabs_level" name="bstabs_level"><option value="all" class="all">' . __('All levels', 'bstabs') . '</option>';
            foreach ($this->getLevels($sInstrument, true) as $level => $levelData)
            {
                $instruments = $levelData['instruments'];
                $instruments[] = 'all'; 
                $checked = $level == $formLevel ? 'selected="selected"' : '';
                $result .= '<option ' . $checked . ' value="' . $level . '" class="' . implode(' ', $instruments) . '">' . $levelData['label'] . '</option>';
            }
            $result .= '    </select>';
            $result .= '  </div>';
            //$result .= '<script charset="utf-8">jQuery(function(jQuery) { jQuery("#bstabs_level").chained("#bstabs_instrument"); });</script>';

            // key search field
            $result .= '  <div class="att">';
            $result .= '    <label for="bstabs_key">' . __('Key', 'bstabs') . ':</label>';
            $result .= '    <select name="bstabs_key"><option value="all">' . __('All keys', 'bstabs') . '</option>';
            foreach ($this->getKeys($sInstrument, true) as $key => $value)
            {
                $checked = $key == $formKey ? 'selected="selected"' : '';
                $result .= '<option ' . $checked . ' value="' . $key . '">' . $value . '</option>';
            }
            $result .= '    </select>';
            $result .= '  </div>';

            // author search field
            $result .= '  <div class="att">';
            $result .= '    <label for="bstabs_author">' . __('Author', 'bstabs') . ':</label>';
            $result .= '    <select id="bstabs_author" name="bstabs_author"><option value="">' . __('All authors', 'bstabs') . '</option>';
            foreach ($this->getAuthors($sInstrument) as $author => $instruments)
            {
                $instruments[] = 'all'; 
                $checked = $author == $formAuthor ? 'selected="selected"' : '';
                $result .= '<option ' . $checked . ' value="' . base64_encode($author) . '" class="' . implode(' ', $instruments) . '">' . $author. '</option>';
            }
            $result .= '    </select>';
            $result .= '  </div>';
            //$result .= '<script charset="utf-8">jQuery(function(jQuery) { jQuery("#bstabs_author").chained("#bstabs_instrument"); });</script>';

            // submit button
            $result .= '  <div class="att">';
            $result .= '    <input type="submit" name="bstabs_submit_button_filter" value="' . __('Search', 'bstabs') . '" />';
            if ($atts['search_all_button'] === 'yes')
                $result .= '    <input type="submit" name="bstabs_submit_button_all" value="' . __('Show all', 'bstabs') . '" />';
            $result .= '  </div>';

            $result .= '<input type="hidden" name="bstabs_action" value="tabs-search" />';
            $result .= '</div>';
            $result .= '</form>';
        } // search form

        // display search results
        $result .= $sResults;

        return $result; 
    }

    public function getValueForKey($key, $array, $default = 'n/a')
    {
        if (!isset($array[$key]))
            return $default;

        if (is_array($array[$key]))
            return $array[$key]['label'];

        return $array[$key];
    }

    public function getIconForMimeType($mimeType)
    {
        $icon = 'default.png';
        switch ($mimeType)
        {
            case 'application/pdf':
                $icon = 'pdf.png';
                break;
            case 'image/jpeg':
            case 'image/gif':
            case 'image/png':
               $icon = 'image.png';
               break;
            case 'application/gpx':
            case 'application/gp':
            case 'application/gp3':
            case 'application/gp4':
            case 'application/gp5':
            case 'application/gtp':
                $icon = 'guitar-pro.png';
                break;
            case 'application/zip':
                $icon = 'archive.png';
                break;
            case 'application/tef':
                $icon = 'tef.png';
                break;
            case 'application/tab':
            case 'application/btab':
                $icon = 'tab.png';
                break; 
            case 'application/mscz':
                $icon = 'musescore.png';
                break;
            case 'audio/mid':
            case 'audio/midi':
                $icon = 'midi.png';
                break;
            case 'audio/mpeg3':
            case 'audio/mpeg':
            case 'audio/midi':
            case 'audio/x-realaudio':
            case 'audio/wav':
            case 'audio/ogg':
            case 'audio/x-ms-wma':
                $icon = 'audio.png';
                break;
            case 'application/tg':
            case 'application/tbl':
                $icon = 'default.png';
        }
        $result = path_join($this->pluginUrl, 'icons');
        $result = path_join($result, $icon);
        return $result;
    }

    public function getInstruments()
    {
        return  array( 'guitar' => __('Guitar', 'bstabs'),
            'banjo' => __('Banjo', 'bstabs'),
            'dobro' => __('Dobro', 'bstabs'),
            'fiddle' => __('Fiddle', 'bstabs'),
            'mandolin' => __('Mandolin', 'bstabs'),
            'bass' => __('Bass', 'bstabs'));
    }

    public function getAuthors($instrument = 'all')
    {
        global $wpdb;
        $result = array();
        $sql = "SELECT DISTINCT i.meta_value as 'instrument', a.meta_value as 'author' FROM wp_postmeta i INNER JOIN wp_postmeta a WHERE i.meta_key='bstabs_instrument' and a.meta_key='bstabs_author' and i.post_id = a.post_id ORDER BY a.meta_value ASC";
        $values = $wpdb->get_results($sql, ARRAY_A);
        foreach ($values as $rec)
        {
            $author = $rec['author'];
            if (!array_key_exists($author, $result))
                $result[$author] = array();

            if (!array_key_exists($rec['instrument'], $result[$author]))
                $result[$author][] = $rec['instrument'];
        }

        // remove all authors not related to specified instrument
        if ($instrument !== 'all')
        {
            foreach ($result as $author => $instruments)
                if (!in_array($instrument, $instruments))
                    unset($result[$author]);
        }
        return $result;
    }

    public function getTunings($instrument = 'all')
    {
        global $wpdb;
        $values = array_unique($wpdb->get_col('SELECT meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key = \'bstabs_tuning\''));
        return $values;
    }

    public function getLevels($instrument = 'all', $onlyUsed = false)
    {
        global $wpdb;

        $result = array(
            'beginner' => array('label' => __('Beginner', 'bstabs'), 'instruments' => array()),
            'intermediate' => array('label' => __('Intermediate', 'bstabs'), 'instruments' => array()),
            'advanced' => array('label' => __('Advanced', 'bstabs'), 'instruments' => array()));

        // remove all levels not related to specified instrument
        if ($onlyUsed)
        {
            //if ($instrument !== 'all')
            //$sqlInstr = $instrument !== 'all' ? " and i.meta_value='" . $instrument . "'" : '';
 
            $sql = "SELECT DISTINCT i.meta_value as 'instrument', l.meta_value as 'level' FROM wp_postmeta i INNER JOIN wp_postmeta l WHERE i.meta_key='bstabs_instrument'" . $sqlInstr . " and l.meta_key='bstabs_level' and i.post_id = l.post_id";
            $values = $wpdb->get_results($sql, ARRAY_A);
            foreach ($values as $rec)
            {
                $level = $rec['level'];
                // skip levels not found in predefined list
                if (!isset($result[$level]))
                    continue;
                if (!in_array($rec['instrument'], $result[$level]['instruments']))
                    $result[$level]['instruments'][] = $rec['instrument'];
            }

            if ($instrument !== 'all')
                foreach ($result as $level => $levelData)
                    if (!in_array($instrument, $levelData['instruments']))
                        unset($result[$level]);
        }

        return $result;
    }

    public function getGenres($instrument = 'all', $onlyUsed = false)
    {
        global $wpdb;

        $result = array(
            'bluegrass' => __('Bluegrass', 'bstabs'),
            'blues' => __('Blues', 'bstabs'),
            'classical' => __('Classical', 'bstabs'),
            'folk' => __('Folk', 'bstabs'),
            'jazz' => __('Jazz', 'bstabs'),
            'rock' => __('Rock', 'bstabs'));

        // remove all genres not related to specified instrument
        if ($onlyUsed)
        {
            if ($instrument !== 'all')
            $sqlInstr = $instrument !== 'all' ? " and i.meta_value='" . $instrument . "'" : '';
        
            $sql = "SELECT DISTINCT g.meta_value as 'genre' FROM wp_postmeta i INNER JOIN wp_postmeta g WHERE i.meta_key='bstabs_instrument'" . $sqlInstr . " and g.meta_key='bstabs_genre' and i.post_id = g.post_id";
            $values = $wpdb->get_col($sql);
            foreach (array_keys($result) as $genre)
                if (!in_array($genre, $values))
                    unset($result[$genre]);
        }

        return $result;
    }

    public function getKeys($instrument = 'all', $onlyUsed = false)
    {
        global $wpdb;

        $result = array(
            'C' => __('C', 'bstabs'),
            'Cm' => __('Cm', 'bstabs'),
            'C#' => __('C#', 'bstabs'),
            'C#m' => __('C#m', 'bstabs'),
            'D' => __('D', 'bstabs'),
            'Dm' => __('Dm', 'bstabs'),
            'Eb' => __('Eb', 'bstabs'),
            'Ebm' => __('Ebm', 'bstabs'),
            'E' => __('E', 'bstabs'),
            'Em' => __('Em', 'bstabs'),
            'F' => __('F', 'bstabs'),
            'Fm' => __('Fm', 'bstabs'),
            'F#' => __('F#', 'bstabs'),
            'F#m' => __('F#m', 'bstabs'),
            'G' => __('G', 'bstabs'),
            'Gm' => __('Gm', 'bstabs'),
            'Ab' => __('Ab', 'bstabs'),
            'Abm' => __('Abm', 'bstabs'),
            'A' => __('A', 'bstabs'),
            'Am' => __('Am', 'bstabs'),
            'Bb' => __('Bb', 'bstabs'),
            'Bbm' => __('Bbm', 'bstabs'),
            'B' => __('B', 'bstabs'),
            'Bm' => __('Bm', 'bstabs'));

        // remove all levels not related to specified instrument
        if ($onlyUsed)
        {
            $sqlInstr = $instrument !== 'all' ? " and i.meta_value='" . $instrument . "'" : '';
            
            $sql = "SELECT DISTINCT k.meta_value as 'key' FROM wp_postmeta i INNER JOIN wp_postmeta k WHERE i.meta_key='bstabs_instrument'" . $sqlInstr . " and k.meta_key='bstabs_key' and i.post_id = k.post_id";
            $values = $wpdb->get_col($sql);
            foreach (array_keys($result) as $key)
                if (!in_array($key, $values))
                    unset($result[$key]);
        }

        return $result;
    }

    // get files attached to tab and sort it according to mime-type
    public function getFiles($postId = NULL)
    {
        global $post;

        $result = array();

        $parentPostId = is_numeric($postId) ? $postId : $post->ID;

        $children = &get_children('post_type=attachment&post_parent=' . $parentPostId);

        foreach ($children as $c)
        {
            // skip not related files
            if (!in_array($c->post_mime_type, array_merge(BSTabs::$mimeTypesTab, BSTabs::$mimeTypesAudio)))
                continue;

            $title = $c->post_title . '&nbsp;(' . basename($c->guid) . ')';

            $result[] = array(
                'url' => wp_get_attachment_url($c->ID),
                'title' => $title,
                'mime-type' =>  $c->post_mime_type);
        }
 
        return $result;
    }

    public function formatFiles($files, $mimeTypes, $template = 'compact')
    {
        $result = '';

        if ($template == 'compact')
        {
            $items = array();
            foreach ($files as $file)
            {
                if (in_array($file['mime-type'], $mimeTypes))
                    $items[] = '<a href="' . $file['url'] . '" title="' . $file['title'] . '"><img alt="' . $file['title'] . '" src="' . $this->getIconForMimeType($file['mime-type']) . '" /></a>';
            }
            $result .= implode('&nbsp;', $items);
        }
        elseif ($template == 'list')
        {
            $items = array();
            foreach ($files as $file)
            {
                if (in_array($file['mime-type'], $mimeTypes))
                    $items[] = '<a href="' . $file['url'] . '" title="' . $file['title'] . '"><img alt="' . $file['title'] . '" src="' . $this->getIconForMimeType($file['mime-type']) . '" />&nbsp; ' . $file['title'] . '</a>';
            }
            $result .= implode('<br />', $items);
        }

        return $result;
    }

    public function formatLinks($links, $template = 'compact')
    {
        $result = '';

        if ($template == 'compact')
        {
            $items = array();
            foreach ($links as $link)
                $items[] = '<a href="' . $link['url'] . '" title="' . $link['title'] . '" target="_blank"><img src="' . $link['icon'] . '"></a>';
            $result .= implode('&nbsp;', $items);
        }
        elseif ($template == 'list')
        {
            $items = array();
            foreach ($links as $link)
                $items[] = '<a href="' . $link['url'] . '" title="' . $link['title'] . '" target="_blank"><img src="' . $link['icon'] . '">&nbsp;' . $link['title'] . '</a>';
            $result .= implode('<br />', $items);
        }

        return $result;
    }

    // get links from string 
    public function getLinks($str)
    {
        $result = array();

        if (!is_string($str))
            return $result;

        // split string to lines
        $lines = explode("\n", str_replace("\r\n","\n", trim($str)));

        foreach ($lines as $line)
        {
            // skip empty lines
            $line = trim($line);
            if (strlen($line) == 0)
                continue;

            // split line to parts (maximum 2)
            $parts = explode(';', $line, 2);

            // try to find an icon
            $icon = 'link.png';
            if (strpos($parts[0], 'youtube') !== false)
                $icon = 'youtube.png';

            $iconUrl = path_join($this->pluginUrl, 'icons');
            $iconUrl = path_join($iconUrl, $icon);

            $result[] = array(
                'url' => $parts[0],
                'title' => count($parts) > 1 ? $parts[1] : $parts[0],
                'icon' => $iconUrl);
        }

        return $result;
    }

}

// create plugin instance
$bsTabs = new BSTabs();  

?>
