all: csfix static-analysis test
	@echo "Done."

vendor: composer.json
	composer update --ignore-platform-reqs
	touch vendor

.PHONY: csfix
csfix: vendor
	vendor/bin/php-cs-fixer fix --verbose

.PHONY: static-analysis
static-analysis: vendor
	$(or ${PHP_BIN},php) vendor/bin/phpstan analyse

wait-for-it:
	wget -O wait-for-it "https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh"
	chmod +x wait-for-it

.PHONY: start-imap-server
start-imap-server: wait-for-it
	docker run --name=ddeboer_imap_server --detach --rm --publish 10993:993 --env MAILNAME=test.test --env MAIL_ADDRESS=test@test.test --env MAIL_PASS=p4ssword antespi/docker-imap-devel:latest
	./wait-for-it localhost:10993

.PHONY: stop-imap-server
stop-imap-server:
	docker stop ddeboer_imap_server

.PHONY: test
test: vendor
	IMAP_SERVER_NAME=localhost IMAP_SERVER_PORT=10993 IMAP_USERNAME=test@test.test IMAP_PASSWORD=p4ssword $(or ${PHP_BIN},php) -d zend.assertions=1 vendor/bin/phpunit ${arg}
