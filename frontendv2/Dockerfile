FROM node:18-alpine

WORKDIR /app

# Install Expo CLI locally instead of globally to avoid compatibility issues
# The newer npx approach is recommended over global installation

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the application
COPY . .

# Expose ports for Expo and React Native
EXPOSE 19000
EXPOSE 19001
EXPOSE 19002
EXPOSE 8081

# Set environment variables for Expo
ENV EXPO_DEVTOOLS_LISTEN_ADDRESS=0.0.0.0

# Start Expo
CMD ["npx", "expo", "start", "--host"]