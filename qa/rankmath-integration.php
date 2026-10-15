<?php
if (get_option('siteurl') !== 'http://localhost:8099') exit(1);
function a($pass,$message){ if (!$pass) {fwrite(STDERR,"FAIL ".$message."\n");exit(1);}echo "PASS ".$message."\n"; }
function call_rest($method,$path,$fields=[]){
 $req=new WP_REST_Request($method,'/advertpreneur-connect/v1/'.$path);
 foreach($fields as $k=>$v)$req->set_param($k,$v);
 $res=rest_do_request($req);return [$res->get_status(),$res->get_data()];
}
wp_set_current_user(1);
[$s,$seo]=call_rest('GET','seo/status');
a($s===200 && $seo['primary']['id']==='rank_math' && !$seo['conflict'],'Rank Math detected alone');
$published=wp_insert_post(['post_type'=>'post','post_status'=>'draft','post_title'=>'Isolated Rank Math QA Fixture '.time(),'post_content'=>'<h2>SEO metadata</h2><p>This is a draft for a private integration test. It is not customer-facing content.</p>'],true);
a(!is_wp_error($published),'private draft fixture created');
$id=(int)$published;
$title='SEO Metadata Validation for an Isolated Test';
$desc='Verify Rank Math meta title, description, focus keyword and canonical are saved to their native WordPress storage.';
[$s,$written]=call_rest('POST','seo/'.$id,['fields'=>['meta_title'=>$title,'meta_description'=>$desc,'focus_keyword'=>'SEO metadata validation','canonical_url'=>'https://example.org/qa/']]);
a($s===200 && !empty($written['ok']),'Rank Math SEO update accepted');
[$s,$read]=call_rest('GET','seo/'.$id);
a($s===200,'Rank Math SEO read works');
$fields=$read['fields']??[];
a(($fields['meta_title']??null)===$title,'Rank Math title persisted and read back');
a(($fields['meta_description']??null)===$desc,'Rank Math description persisted and read back');
a(($fields['focus_keyword']??null)==='SEO metadata validation','Rank Math focus keyword persisted and read back');
[$s,$audit]=call_rest('GET','seo/'.$id.'/audit');
a($s===200,'Rank Math SEO audit responds');
wp_delete_post($id,true);
echo "RANKMATH_QA_PASSED\n";
