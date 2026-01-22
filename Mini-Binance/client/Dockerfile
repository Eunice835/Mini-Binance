FROM node:20-alpine

WORKDIR /app

# Copy package.json files
COPY package*.json ./

# Skip npm install in Docker (we'll use host-installed node_modules)
# RUN npm install

# Copy locally installed node_modules from your host
COPY node_modules ./node_modules

# Copy the rest of the source code
COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host"]
