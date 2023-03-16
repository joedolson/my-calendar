if [ ! -f './.husky/_/husky.sh' ]; then
	npx husky install
fi

if [ ! -f './.husky/pre-commit' ]; then
	npx husky add './.husky/pre-commit' 'npx lint-staged'
fi
