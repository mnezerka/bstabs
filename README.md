# BSTabs

BSTabs extends WordPress of simple tabulature management.

BSTabs plugin registers new post type of type Tab which serves as container for
files (gpx, audio, pdf, etc.) and information (instrument, genre, level, etc.)
related to one specific tabulature. New shortcode `[tabs]` could be inserted in
any post content and allows listing and searching in all tabulatures according
attributes. 

Shortcode `[tabs]`, in its simple form (without any parameters), lists all
tabulatures. Use one or more of the following parameters to change tab listing
or show search dialog:

* `instrument` - (optional) limit list of tabs to specific instrument. (possible values: all, banjo, guitar, mandolin, fiddle, bass; default value: all).
* `search` - optional parameter for showing search dialog with controls for various tab metadata attributes. (possible values: yes, no; default value: no) 
* `search_all_button` - optional parameter for showing additional button in search dialog. This button allows users to show all tabs and ignore all search dialog filters. (possible values: yes, no; default value: no) 
* `count` - maximal number of listed tabulatures
* `orderby` - optional parameter to set fields for order of tabulatures, acceptable fields are `author`, `title` and `published` . Default value is `title`.
* `order` - optional parameter which provides additional information to `orderby` parameter. It specifies if list is sorted ascending or descending.Possible values are `asc` and `desc`. Default value is `asc`.
* `fields` - optional parameter which allows to enumerate tabulature fields (attributes) to be shown in listing. Parameter value must be list of field identifiers separated by semicolon. Allowed identifiers are: `title`, `instrument`, `author`, `key`, `level`, `genre`, `tuning`, `audio`, `tabs`, `links` and `published`.
* `class` - optional parameter to set additional CSS class for listing generated by shortcode

## Examples

```
[tabs] - list all tabulatures

[tabs search="yes"] - show search dialog

[tabs search="yes" instrument="banjo"] - show search dialog, but limit all search queries to banjo instrument.

[tabs instrument="guitar"] - list all guitar tabulatures

[tabs count="5" orderby="published" order="desc" fields="title;instrument;tabs;published" class="compact"] - list last five published tabulatures, most recent tab is listed first, listing is limited to selected columns (attributes) and generated HTML code is extended of class `raw`.
```

## Installation

1. Install and activate the plugin through the 'Plugins' menu in WordPress.
2. Assign new capabilities to individual roles according to your security policy. 
3. Add and edit tabulatures in admin section - there is a new menu item for it.
4. Use shorttag [tabs] to insert list of tabulatures into post content
5. That's it! :)

## Changelog

### 1.14

* another set of fixes for ordering by title with qtranslate plugin, this
  version solves three cases:

    * title is not translated at all
    * title is translated in older qtranslate plugin, where notation is ` <!--:en-->Title<!--:-->`
    * title is translated in qtranslate plugin, where notation is `[:en]Title[:]`

### 1.13

* fix problem with `fields` parameter of `tabs` shortcode (`split` method was
  removed from PHP)
* tabs search form uses URL query parameters to allow users to generate and
  share link to pre-filled form
* fix ordering by title in cases, where qtranslate plugin is used for
  multilingual content

### 1.12

* registration of new media type with extension `mscz` for MuseScroe tabs
 
### 1.11

* bugfix: correction of links generated by tabs shortcode
 
### 1.10

* column published is localized to Czech
* column published shows only date (without time) 
 
### 1.9

* new parameter `count` for shortcode `tabs` to limit number of listed tabulatures 
* new parameter `orderby` and `order` for shortcode `tabs` to set custom order of listed tabulatures 
* new parameter `fields` to specify which fields are shown in listing of tabulatures
* new parameter `class` allows setting of CSS class for generated listing

### 1.8

* bugfix: files uploaded to tabulatures can have special characters (e.g. %) in file names. This is known bug in Wordpress which is not fixed by Wordpress developers.
* titles for files are taken from file metadata rather from physical file name 
* tabulature links are opened in new window
* new default icon for audio files

### 1.7

* added new tabulature attribute - text filed for entering related links. Links are shown as icons with description in admin area (list of tabulatures), in list generated by tabs shortcode and in detail tab view generated by tabs shortcode. 

### 1.6

* tabs listing generated by tabs shortcode is sorted by tab title
* bugfix: searching by author for names with czech characters should work
* list of authors in search dialog combobox is sorted alphabeticaly
* two columns were added to list of tabulatures in admin section: list of tab attachments and list of audio attachments

### 1.5

* new icon for mid and midi file type 
* ra, ram, wav, mid, midi, ogg, oga, wma file types are treated as audio files when displayed in tab listing or tab detail view
* tab attachment icons in tabs listing have title (file name) when mouse is moved over image 

### 1.4

* translation to Czech for "Tune" and all keys (especially B and Bm)
* added new icons for tabs of type tab, btab and tef

### 1.3

* registered new media types for wordpress upload (gp, .gp3, .gp4, .gp5, .gpx, .gtp, .tef, .tab, .btab, .tg, .tbl) 
* autocomplete implemented for several tab edit dialog controls (author, tuning)
* new instrument type: fiddle
* removed tab attribute "transcriber"
* added tab attribute "tuning"
* added optional button to search dialog for listing of all tabulatures without filtering 
* it is possible to select "-" value for key, level and genre
* bugfix: limit list of authors and tunings in search dialog to relevant subsets (don't show values related to other instruments) 


### 1.2

* bugfixing

### 1.1

* implemented i18n - all visible strings are exported (.po, .pot, .mo) for possible translation
* added Czech translation
* bugfix - list of tabs is empty when search dialog is enabled

### 1.0

* Initial release 

### Uninstall

1. Deactivate the plugin
2. That's it! :)
