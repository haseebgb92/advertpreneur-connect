<?php
// Disposable WordPress integration test. Must run only in adpc-qa Docker stack.
if (get_option('siteurl') !== 'http://localhost:8099') { fwrite(STDERR, "Wrong WordPress site!\n"); exit(1); }
if (!is_plugin_active('advertpreneur-connect/advertpreneur-connect.php')) { fwrite(STDERR,"Plugin not active\n"); exit(1); }
if (!function_exists('rest_do_request')) { fwrite(STDERR, "REST unavailable\n"); exit(1); }

function adpc_assert($condition, $message) {
  if (!$condition) { fwrite(STDERR,"FAIL: ".$message."\n");exit(1); }
  echo "PASS: ".$message."\n";
}
function adpc_rest($method, $endpoint, $params=[]) {
  $r = new WP_REST_Request($method, '/advertpreneur-connect/v1/'.$endpoint);
  foreach ($params as $key=>$value) $r->set_param($key, $value);
  $response=rest_do_request($r);
  return ['code'=>$response->get_status(),'data'=>$response->get_data()];
}
wp_set_current_user(1);
// Clean only historical disposable QA pages on localhost before repeat tests.
foreach (get_posts(['post_type'=>'page','post_status'=>'any','posts_per_page'=>500]) as $existing_fixture) {
    if (strpos($existing_fixture->post_title,'Advertpreneur Agensi QA Fixture ')===0) wp_delete_post($existing_fixture->ID, true);
}
adpc_assert(adpc_rest('GET','status')['data']['version']==='0.7.0','plugin status reports 0.7.0');
$sections=[
['Planning the publication','Begin with an explicit audience and a specific answer to the reader question. Examine related articles already on the website so the new draft contributes distinct information rather than repeating previous material. Record the intended outcome before writing, including which question the page should answer and which claims require support.'],
['Building accurate content','Construct the article from verifiable information, relevant examples and a straightforward introduction. Headings should reflect the progression of ideas rather than promotional phrases. Include internal links only after checking that the destinations are published and contextually relevant, and keep each explanation accurate to the evidence available.'],
['Editing and review','Check every paragraph for redundancy, misleading certainty and unsupported numbers. When a statement could change, identify an authoritative source rather than asserting the detail without evidence. Ensure images have legitimate usage rights and descriptions that describe the artwork naturally for screen-reader users and other visitors.'],
['Publishing responsibility','Save the output as a draft so a human reviewer can inspect the finished content in context. Confirm the correct destination, taxonomy, slug and proposed featured image before making the article public. Resolve structural quality findings and check topical overlap immediately before approval, since edits can invalidate an earlier assessment.']
];
$html='';
foreach($sections as [$heading,$body]) $html.='<h2>'.esc_html($heading).'</h2><p>'.esc_html($body).'</p>';
$title='Advertpreneur Agensi QA Fixture '.date('YmdHis');
$draft=adpc_rest('POST','drafts',['type'=>'page','title'=>$title,'content'=>$html,'slug'=>'adpc-qa-fixture-'.time()]);
adpc_assert($draft['code']<300 && !empty($draft['data']['post_id']),'draft created through real WordPress REST');
$id=(int)$draft['data']['post_id'];
adpc_assert(get_post_status($id)==='draft','WordPress persisted draft');
$check=adpc_rest('POST','quality/check',['post_id'=>$id]);
adpc_assert(!empty($check['data']['pass']),'WordPress quality check passes');
$overlap=adpc_rest('POST','quality/overlap',['post_id'=>$id]);
adpc_assert(!empty($overlap['data']['pass']),'WordPress site-overlap check passes');

$author=wp_insert_user(['user_login'=>'adpc_qa_contributor_'.time(),'user_pass'=>'LocalQAContributorPassOnly','user_email'=>'author'.time().'@localhost.invalid','role'=>'contributor']);
adpc_assert(!is_wp_error($author),'restricted contributor created');
wp_set_current_user($author);
$not_allowed=adpc_rest('POST','quality/attest',['post_id'=>$id,'report'=>['passed'=>true,'site_overlap_checked'=>true,'repetition_checked'=>true,'misleading_claims_checked'=>true,'source_reviewed'=>true]]);
adpc_assert($not_allowed['code']===403,'unrelated contributor cannot attest another authors draft');
wp_set_current_user(1);
$report=['passed'=>true,'site_overlap_checked'=>true,'repetition_checked'=>true,'misleading_claims_checked'=>true,'source_reviewed'=>true,'content_sha256'=>$check['data']['content_sha256'],'notes'=>'Controlled QA content, no external factual claims','sources'=>[]];
$attested=adpc_rest('POST','quality/attest',['post_id'=>$id,'report'=>$report]);
adpc_assert($attested['code']<300 && !empty($attested['data']['ok']),'passing quality attestation saved');
$edited=adpc_rest('PATCH','posts/'.$id,['title'=>$title.' Edited']);
adpc_assert($edited['code']<300,'editing title succeeds');
$blocked=adpc_rest('POST','posts/'.$id.'/publish',[]);
adpc_assert(in_array($blocked['code'],[409,422],true),'stale quality approval blocks publication');
$check=adpc_rest('POST','quality/check',['post_id'=>$id]);
$report['content_sha256']=$check['data']['content_sha256'];
$attested=adpc_rest('POST','quality/attest',['post_id'=>$id,'report'=>$report]);
adpc_assert($attested['code']<300,'updated content reattested');
$published=adpc_rest('POST','posts/'.$id.'/publish',[]);
adpc_assert($published['code']<300 && ($published['data']['status']??'')==='publish','authorized publish persists WordPress status');
$retry=adpc_rest('POST','posts/'.$id.'/publish',[]);
adpc_assert(!empty($retry['data']['already_published']),'publication retry idempotent');
echo "QA_POST_ID=".$id." QA_TESTS_PASSED\n";
