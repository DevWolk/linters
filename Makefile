# - IGNORE ERROR; @ - SUPPRESS COMMAND VERBOSITY; PHONY used to ensure that command is always executed even if a file with the same name exists;
.SILENT: \
	fix-syntax-completely

MAKEFLAGS += --no-print-directory # Forced blocking of directory printing

## HELPERS
include ./make/helpers.mk
