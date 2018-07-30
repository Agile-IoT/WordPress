var request = require('request');
var fs = require('fs');
var content = fs.readFileSync('caps.json');
var json = JSON.parse(content);
var read_caps = ["read", "read_post", "read_private_pages", "read_private_posts", "list_users", "export", "export_others_personal_data"];
var token = process.argv[2];
console.log(token);
var policies = {};
for(var role in json) {
	if(json.hasOwnProperty(role)) {
		var caps = json[role].capabilities;
		for(var cap in caps) {
			if(caps.hasOwnProperty(cap)) {
//				console.log(JSON.stringify(policies[cap]), cap, role, policies[cap] === undefined);
				if (policies[cap] === undefined) {
					policies[cap] = [];
				}
				policies[cap].push({
					op: read_caps.includes(cap) ? 'read' : 'write',
					locks: [{
						lock: 'attrEq',
						args: ['role', role]
						}]
					});
			}
		}
	}
}

for(var policy in policies) {
	if(policies.hasOwnProperty(policy)){
		request({ url: 'http://localhost:2000/agile-security/api/v1/pap/client/wordpress/actions.' + policy,
			method: 'PUT',
			json: {policy: policies[policy]}},
			res => {console.log('done')}).auth(null, null, true, token);
	}
}
