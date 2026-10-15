# Auto detect text files and perform LF normalization
* text=lf

.gitattributes merge=ours
composer.json merge=ours
composer.lock merge=ours
symfony.lock merge=ours
package.json merge=ours
package-lock.json merge=ours
README.md merge=ours
.idea/ merge=ours
docs/ merge=ours

/.docker/ export-ignore
/.github export-ignore
/.idea/ export-ignore
/build/ export-ignore
/docs/ export-ignore
/tests/ export-ignore
/vendor/ export-ignore
/var/ export-ignore
/public/.gitkeep export-ignore

/.editorconfig export-ignore
/.gitattributes export-ignore
/.gitignore export-ignore
/compose.yaml export-ignore
/phpunit.xml.dist export-ignore
/rector.php export-ignore
/sonar-project.properties export-ignore
