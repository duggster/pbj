mysqldump -u pbjadmin -ppbjadmin -h 127.5.16.1 -B pbj --skip-comments --skip-extended-insert --no-data > pbj_openshift.ddl
mysqldump -u pbjadmin -ppbjadmin -h 127.5.16.1 -B pbj --skip-comments --skip-extended-insert --tables user web_module web_module_prop web_module_role > pbj_openshift.dml