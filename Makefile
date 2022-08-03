PHP_DOCKER_VERSION := thecodingmachine/php:8.0-v4-cli
PHP_BIN := docker run -it --rm \
	--network=ddeboer_imap_network \
	--env IMAP_SERVER_NAME=ddeboer_imap_server \
	--env IMAP_SERVER_PORT=993 \
	--env IMAP_USERNAME=test@test.test \
	--env IMAP_PASSWORD=p4ssword \
	--env PHP_EXTENSION_IMAP=1 \
	--env PHP_EXTENSION_PCOV=1 \
	-v "$(PWD)":"$(PWD)" -w "$(PWD)" \
	$(PHP_DOCKER_VERSION)

all: csfix static-analysis test
	@echo "Done."

vendor: composer.json
	$(PHP_BIN) composer update
	touch vendor

.PHONY: csfix
csfix: vendor
	$(PHP_BIN) vendor/bin/php-cs-fixer fix --verbose

.PHONY: static-analysis
static-analysis: vendor
	$(PHP_BIN) vendor/bin/phpstan analyse

wait-for-it:
	wget -O wait-for-it "https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh"
	chmod +x wait-for-it

.PHONY: start-imap-server
start-imap-server: wait-for-it
	docker pull $(PHP_DOCKER_VERSION)
	docker pull antespi/docker-imap-devel:latest
	docker network create ddeboer_imap_network
	docker run \
		--name=ddeboer_imap_server \
		--network=ddeboer_imap_network \
		--detach \
		--rm \
		--expose 993 \
		--publish 10993:993 \
		--env MAILNAME=test.test \
		--env MAIL_ADDRESS=test@test.test \
		--env MAIL_PASS=p4ssword \
		antespi/docker-imap-devel:latest
	./wait-for-it localhost:10993

.PHONY: stop-imap-server
stop-imap-server:
	docker stop ddeboer_imap_server

.PHONY: test
test: vendor
	$(PHP_BIN) php \
		-d zend.assertions=1 \
		vendor/bin/phpunit \
		$(PHPUNIT_FLAGS)
