var output = '<Policy xmlns="urn:oasis:names:tc:xacml:3.0:core:schema:wd-17"  PolicyId="wordpress" RuleCombiningAlgId="urn:oasis:names:tc:xacml:1.0:rule-combining-algorithm:first-applicable" Version="1.0">' +
   '<Description>Test</Description>' +
   '<Target></Target>' +
   '<Rule Effect="Permit" RuleId="primary-group-emps-rule">' +
      '<Target><AnyOf>';


var fs = require("fs");
var readCaps = ["read", "read_post", "read_private_pages", "read_private_posts", "list_users", "export", "export_others_personal_data"];
function getMethod(cap) {
	return readCaps.find(read => {return cap === read;}) === undefined ? "write" : "read";
}

var content = JSON.parse(fs.readFileSync("../caps.json"));
var roles = [];
for(var key in content) {
	if(content.hasOwnProperty(key)) {
		var caps = content[key].capabilities;
		var method;
		for(var cap in caps) {
			if(caps.hasOwnProperty(cap)) {
				method = getMethod(cap);
				output += '<AllOf>';
				output += '<Match MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">' +
                  			'<AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">' + cap + '</AttributeValue>' +
                  			'<AttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:resource:resource-id" Category="urn:oasis:names:tc:xacml:3.0:attribute-category:resource" DataType="http://www.w3.org/2001/XMLSchema#string" MustBePresent="true"></AttributeDesignator>' +
               				'</Match>';
				output += '<Match MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">' +
                		 	'<AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">' + method + '</AttributeValue>' +
                  			'<AttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:action:action-id" Category="urn:oasis:names:tc:xacml:3.0:attribute-category:action" DataType="http://www.w3.org/2001/XMLSchema#string" MustBePresent="true"></AttributeDesignator>' +
 		              		'</Match>' +
               				'<Match MatchId="urn:oasis:names:tc:xacml:1.0:function:string-equal">' +
         		         	'<AttributeValue DataType="http://www.w3.org/2001/XMLSchema#string">' + key + '</AttributeValue>' +
                  			'<AttributeDesignator AttributeId="urn:oasis:names:tc:xacml:1.0:subject:role" Category="urn:oasis:names:tc:xacml:1.0:subject-category:access-subject" DataType="http://www.w3.org/2001/XMLSchema#string" MustBePresent="true"></AttributeDesignator>' +
               				'</Match>';
				output += '</AllOf>';
			}
		}
	}
}






output += '</AnyOf></Target>' +
   '</Rule>' +
   '<Rule Effect="Deny" RuleId="deny-rule"></Rule>' +
'</Policy>';
fs.writeFile("caps.xml", output, function(err) {
    if(err) {
        return console.log(err);
    }
    console.log("The file was saved!");
}); 

//console.log(output);
