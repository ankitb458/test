<?php
/*
Author: Davide Pozza
Author URI: http://www.nothing2hide.net/
Description: Dynamically translates a blog in thirteen different languages (English, French, Italian, German, Portuguese, Spanish, Japanese, Korean, Chinese, Arabic, Russian, Greek, Dutch) by wrapping the Google Translation Engine or the Babelfish Translation Engine
*/


include_once('header.php');

load_plugin_textdomain('gltr'); // NLS

/*Lets add some default options if they don't exist*/
add_option('gltr_base_lang', 'en');
add_option('gltr_col_num', '0');
add_option('gltr_use_cache', false);
add_option('gltr_cache_timeout', '3600');
add_option('gltr_html_bar_tag', 'TABLE');
add_option('gltr_my_translation_engine', 'babelfish');
add_option('gltr_preferred_languages', array());

$location = get_option('siteurl') . '/wp-admin/admin.php?page=global-translator/options-translator.php'; // Form Action URI

/*check form submission and update options*/

if (isset($_POST['stage'])){
	check_admin_referer('translator');
	//submitting something
	$gltr_base_lang 						= $_POST['gltr_base_lang'];
	$gltr_col_num 							= $_POST['gltr_col_num'];
	$gltr_cache_timeout 				= $_POST['gltr_cache_timeout'];
	$gltr_html_bar_tag 					= $_POST['gltr_html_bar_tag'];
	$gltr_my_translation_engine = $_POST['gltr_my_translation_engine'];
	if(isset($_POST['gltr_use_cache']))
		$gltr_use_cache = true;
	else
		$gltr_use_cache = false;
	if (!isset($_POST['gltr_preferred_languages']))
		$gltr_preferred_languages = array();
	else
		$gltr_preferred_languages = $_POST['gltr_preferred_languages'];


	if ('change' == $_POST['stage']) {
		//recalculate some things
	} else if ('process' == $_POST['stage']){
	  if(!empty($_POST["gltr_erase_cache"])) {
	  	//Erase cache button pressed
	    $cachedir =  ABSPATH . 'wp-content/translations';

	    if (!file_exists($cachedir)) {
	      @mkdir($cachedir, 0777);
	    }

	    if (file_exists($cachedir) && is_dir($cachedir) && is_readable($cachedir)) {
	      $handle = opendir($cachedir);
	      while (FALSE !== ($item = readdir($handle))) {
	        if($item != '.' && $item != '..') {
	          $path = $cachedir.'/'.$item;
	          unlink($path);
	        }
	      }
	      $message = "Cache successfully erased";
	    } else {
	      $message = "Unable to erase cache or cache dir not existing";
	    }
	  } else {
	  	//update options button pressed
	  	$iserror = false;
	    $timeout = $_POST['gltr_cache_timeout'];

	    if(!$iserror) {
	      if ($timeout == "") $timeout = "3600";
	      update_option('gltr_base_lang', $_POST['gltr_base_lang']);
	      update_option('gltr_col_num', $_POST['gltr_col_num']);
	      update_option('gltr_cache_timeout', $timeout);
	      update_option('gltr_html_bar_tag', $_POST['gltr_html_bar_tag']);
	      update_option('gltr_my_translation_engine', $_POST['gltr_my_translation_engine']);
	      update_option('gltr_preferred_languages', array());
	      update_option('gltr_preferred_languages', $_POST['gltr_preferred_languages']);

	      if(isset($_POST['gltr_use_cache']))
	        update_option('gltr_use_cache', true);
	      else
	        update_option('gltr_use_cache', false);

				$wp_rewrite->flush_rules();
	      $message = "Options saved.";
	    }
	  }
	}
} else {
	//page loaded by menu: retrieve stored options
	$gltr_base_lang = get_option('gltr_base_lang');
	$gltr_col_num = get_option('gltr_col_num');
	$gltr_use_cache = get_option('gltr_use_cache');
	$gltr_cache_timeout = get_option('gltr_cache_timeout');
	$gltr_html_bar_tag = get_option('gltr_html_bar_tag');
	$gltr_my_translation_engine = get_option('gltr_my_translation_engine');
	$gltr_preferred_languages = get_option('gltr_preferred_languages');

}



/*Get options for form fields*/
$current_engine = $gt_available_engines[$gltr_my_translation_engine];
if (!$current_engine) $current_engine = $gt_available_engines['google'];


function gltr_build_js_function($base_lang, $selected_item) {
	global $current_engine;
?>
<script type="text/javascript">
calculateOptions('<?php echo $base_lang ?>', <?php echo $selected_item ?>);

function languageItem(lang, flags_num){
  this.lang=lang;
  this.flags_num=flags_num;
}

function calculateOptions(lang, selectedItem) {
  var flags_num = 0;
  var list = new Array();
<?php
  $languages = $current_engine->get_languages_matrix();
  $j=0;
  foreach($languages as $key => $value){
    echo "  list[$j] = new languageItem('$key', " . count($languages[$key]) . ");\n";
    $j++;
  }
?>
  for (z = 0; z < document.forms['form1'].gltr_col_num.options.length; z++) {
    document.forms['form1'].gltr_col_num.options[z] = null;
  }
  document.forms['form1'].gltr_col_num.options.length = 0;

  for (y = 0; y < list.length; y++) {
    if (list[y].lang == lang){
      flags_num = list[y].flags_num;
      break;
    }
  }
  for (i = 0; i <= flags_num; i++) {
    if (i == 0) {
      opt_text='all the flags in a single row (default)';
    } else if (i == 1) {
      opt_text='1 flag for each row';
    } else {
      opt_text= i + ' flags for each row';
    }
    document.forms['form1'].gltr_col_num.options[i]=new Option(opt_text, i);
  }

  //I need to cycle again on the options list in order to correctly choose the selected item
  for (i = 0; i <= flags_num; i++) {
    document.forms['form1'].gltr_col_num.options[i].selected = (selectedItem == i);
  }
}

function calculateAvailableTranslations(lang, selectedItem) {
  var list = new Array();
<?php
  $languages = $current_engine->get_languages_matrix();
  $j=0;
  foreach($languages as $key => $value){
    echo "  list[$j] = new languageItem('$key', " . count($languages[$key]) . ");\n";
    $j++;
  }
?>
  for (z = 0; z < document.forms['form1'].gltr_col_num.options.length; z++) {
    document.forms['form1'].gltr_col_num.options[z] = null;
  }
  document.forms['form1'].gltr_col_num.options.length = 0;

  for (y = 0; y < list.length; y++) {
    if (list[y].lang == lang){
      flags_num = list[y].flags_num;
      break;
    }
  }
  for (i = 0; i <= flags_num; i++) {
    if (i == 0) {
      opt_text='all the flags in a single row (default)';
    } else if (i == 1) {
      opt_text='1 flag for each row';
    } else {
      opt_text= i + ' flags for each row';
    }
    document.forms['form1'].gltr_col_num.options[i]=new Option(opt_text, i);
  }

  //I need to cycle again on the options list in order to correctly choose the selected item
  for (i = 0; i <= flags_num; i++) {
    document.forms['form1'].gltr_col_num.options[i].selected = (selectedItem == i);
  }
}
</script>
<?php
}

//Print out the message to the user, if any
if($message!="") { ?>

	<div class="updated"><strong><p>
<?php	echo $message; ?>
	</p></strong></div>

<?php } ?>

<form name="test"></form>
<div class="wrap">
  <h2><?php _e('Translator ')?></h2>
  <form id="gt_form" name="form1" method="post" action="<?php echo $location ?>">
  	<?php if ( function_exists('wp_nonce_field') ) wp_nonce_field('translator'); ?>
  	<input type="hidden" name="stage" value="process" />

    <fieldset class="options">
  		<legend><?php _e('Base settings') ?></legend>
    		<table width="100%" cellpadding="5" class="editform"><tr><td>
          <label><?php _e('My Blog is written in:') ?>
            <select name="gltr_base_lang">
              <?php
              $languages = $current_engine->get_available_languages();
              foreach($languages as $key => $value){
                if ($gltr_base_lang == $key) {
              ?>
              <option value="<?php echo $key ?>" selected ><?php echo $value ?></option>
              <?php
                } else {
              ?>
              <option value="<?php echo $key ?>"  ><?php echo $value ?></option>
              <?php
                }
              }
              ?>
            </select>
          </label>
        </td></tr>
        </table>
     </fieldset>

  	<fieldset class="options">
  		<legend><?php _e('Cache') ?></legend>
  		<table width="100%" cellpadding="5" class="editform">
  		<tr><td>
        <label>
        <input type="submit" name="gltr_erase_cache" value="<?php _e('Erase cache') ?> &raquo;" />
        </label>
      </td></tr>
      </table>
    </fieldset>

    <p class="submit">
      <input type="submit" name="gltr_save" value="<?php _e('Update options') ?> &raquo;" />
    </p>
  </form>
</div>

<?php
#gltr_build_js_function($gltr_base_lang, $gltr_col_num);
?>