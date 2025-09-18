import { config } from 'dotenv';
import { join } from 'path';

// Załaduj zmienne środowiskowe z pliku .env.test
config({ path: join(__dirname, '..', '.env.test') });

export const testConfig = {
  baseURL: process.env.BASE_URL || 'http://localhost:8080',
  
  // Dane użytkowników
  admin: {
    email: process.env.ADMIN_EMAIL || 'admin@example.com',
    password: process.env.ADMIN_PASSWORD || 'password123',
  },
  
  user: {
    email: process.env.USER_EMAIL || 'user@example.com', 
    password: process.env.USER_PASSWORD || 'password123',
  },
  
  // Timeouts
  timeouts: {
    default: parseInt(process.env.DEFAULT_TIMEOUT || '10000'),
    navigation: parseInt(process.env.NAVIGATION_TIMEOUT || '30000'),
  },
};
