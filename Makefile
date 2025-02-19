-include .env

export GO111MODULE=on
export CGO_ENABLED=0

# colors
GREEN=\033[1;32m
PURPLE=\033[1;35m
NC=\033[0m

# compose deps
compose:
	@echo 'compose deps'
	docker-compose -f docker-compose.yaml up -d

# down deps
compose-down:
	@echo 'compose deps'
	docker-compose -f docker-compose.yaml down

# migrate
migrate:
	@echo "\n${GREEN}UP MIGRATE DB${NC}\n"
	@docker run -e INSTALL_MYSQL=true --rm -it \
		  -v ./dev/liquibase/changelogs/karma8/changelog.sql:/liquibase/changelog/changelog.sql \
		  --env-file dev/liquibase/liquibase.docker.karma8.env \
		  liquibase/liquibase update --log-level info

# migrate-rollback
migrate-down:
	@echo "\n${PURPLE}ROLLBACK MIGRATE DB${NC}\n"
	@docker run -e INSTALL_MYSQL=true --rm -it \
		  -v ./dev/liquibase/changelogs/karma8/changelog.sql:/liquibase/changelog/changelog.sql \
		  --env-file dev/liquibase/liquibase.docker.karma8.env \
		  liquibase/liquibase rollback-count --count=1
