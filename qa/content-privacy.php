<?php
// Disposable QA only. The runner maps this into its isolated WP instance.
if (get_option('siteurl') !== 'http://localhost:8099') { fwrite(STDERR, "Wrong site\n"); exit(1); }
function pr_assert($ok,$message) { if (!$ok) {fwrite(STDERR,"FAIL: ".$message."\n");exit(1);} echo "PASS: ".$message."\n"; }
function pr_rest($method,$path,$params=[]) {
  $r=new WP_REST_Request($method,'/advertpreneur-connect/v1/'.$path);
  foreach($params as $key=>$value)$r->set_param($key,$value);
  $res=rest_do_request($r);
  return [$res->get_status(),$res->get_data()];
}
$marker='ADPC_PRIVATE_SEARCH_QA_'.time();
$u=wp_insert_user(['user_login'=>'adpc_qa_private_writer_'.time(),'user_pass'=>'local-qa-only','user_email'=>'private-writer'.time().'@localhost.invalid','role'=>'contributor']);
$v=wp_insert_user(['user_login'=>'adpc_qa_private_reader_'.time(),'user_pass'=>'local-qa-only','user_email'=>'private-reader'.time().'@localhost.invalid','role'=>'contributor']);
pr_assert(!is_wp_error($u) && !is_wp_error($v),'two distinct contributor accounts created');
wp_set_current_user(1);
$draft=wp_insert_post(['post_title'=>$marker,'post_type'=>'post','post_status'=>'draft','post_author'=>$u,'post_content'=>'<p>'.$marker.' private content that belongs to the first author and should never be visible to unrelated contributors.</p>']);
pr_assert($draft>0,'private editorial draft created');
wp_set_current_user($v);
[$status,$result]=pr_rest('GET','content/search',['q'=>$marker]);
pr_assert($status===200 && empty($result['items']),'unrelated contributor cannot discover hidden draft by search');
[$status,$result]=pr_rest('POST','internal-links/suggest',['post_id'=>$draft]);
pr_assert($status===403,'unrelated contributor cannot probe hidden draft through internal-link suggestions');
[$status,$result]=pr_rest('GET','content/'.$draft);
pr_assert($status===403,'unrelated contributor cannot retrieve hidden draft directly');
$own_draft=wp_insert_post(['post_title'=>$marker,'post_type'=>'post','post_status'=>'draft','post_author'=>$v,'post_content'=>'<p>'.$marker.' private content that belongs to a separate writer with similar title.</p>']);
[$status,$overlap]=pr_rest('POST','quality/overlap',['post_id'=>$own_draft]);
pr_assert($status===200 && !in_array($draft,array_column($overlap['matches']??[],'post_id'),true),'similarity results never disclose hidden drafts by another author');
wp_set_current_user($u);
[$status,$result]=pr_rest('GET','content/search',['q'=>$marker]);
pr_assert($status===200 && count($result['items'])===1 && $result['items'][0]['id']===$draft,'draft author can find their own draft');
wp_set_current_user(1);
wp_delete_post($draft,true);
wp_delete_post($own_draft,true);
require_once ABSPATH.'wp-admin/includes/user.php';
wp_delete_user($u);
wp_delete_user($v);
echo "PRIVACY_QA_PASSED\n";
