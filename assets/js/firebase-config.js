import { initializeApp } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-firestore.js";

const firebaseConfig = {
  apiKey: "AIzaSy...",
  authDomain: "verbum-bd.firebaseapp.com",
  projectId: "verbum-bd",
  storageBucket: "verbum-bd.firebasestorage.app",
  messagingSenderId: "845475040699",
  appId: "1:845475040699:web:a5f6187cb6f8ac4403889e"
};

const app = initializeApp(firebaseConfig);

const auth = getAuth(app);
const db = getFirestore(app);

// exportar para usar em outros arquivos
export { auth, db };