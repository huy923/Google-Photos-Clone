# Use Node version 20
FROM node:20

WORKDIR /app

# Copy package.json and yarn.lock first to optimize cache
COPY package.json yarn.lock ./

RUN yarn install

# Copy all source code
COPY . .
# RUN if [ ! -f .env ]; then cp .env.example .env.local; fi
# RUN if [ -f .env.local ]; then echo ".env.local found ✅"; cat .env.local; else echo "⚠️ No .env.local found"; fi
# Build project (if running production)
RUN yarn build

EXPOSE 3000

# Use for dev, can change "yarn start" -> "yarn dev"
CMD ["yarn", "dev"]
