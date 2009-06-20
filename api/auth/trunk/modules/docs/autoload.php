<?php
cmd::attach('docs', 'doc_list');
cmd::attach('doc_cats', 'doc_cat_list');
cmd::attach('new_doc', 'doc_editor');
cmd::attach('edit_doc', 'doc_editor');
cmd::attach('new_doc_cat', 'doc_cat_editor');
cmd::attach('edit_doc_cat', 'doc_cat_editor');

event::attach('register_caps', array('doc', 'register_caps'));
?>