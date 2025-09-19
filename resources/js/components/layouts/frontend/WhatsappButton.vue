<template>
  <a
    :href="whatsappUrl"
    target="_blank"
    rel="noopener noreferrer"
    class="whatsapp-button"
  >
    <img
      src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
      alt="WhatsApp"
      class="whatsapp-icon"
    />
  </a>
</template>

<script>
export default {
  name: "WhatsAppButton",
  props: {
    phone: {
      type: String,
      required: true,
    },
    message: {
      type: String,
      default: "Olá, gostaria de mais informações!",
    },
    text: {
      type: String,
      default: "Fale conosco pelo WhatsApp",
    },
  },
  computed: {
    whatsappUrl() {
      const encodedMessage = encodeURIComponent(this.message);
      return `https://wa.me/${this.phone}?text=${encodedMessage}`;
    },
  },
};
</script>

<style scoped>
.whatsapp-button {
  position: fixed;
  bottom: 0;
  right: 15px;
  display: flex;
  align-items: center;
  text-decoration: none;
  background-color: #25d366;
  color: white;
  padding: 10px 15px;
  margin: 15px;
  border-radius: 5px;
  font-size: 16px;
  font-weight: bold;
  transition: background-color 0.3s;
  z-index: 1000;
}

.whatsapp-button:hover {
  background-color: #1da851;
}

.whatsapp-icon {
  width: 20px;
  height: 20px;
}

.button-text {
  margin-left: 10px;
}

@media (max-width: 768px) {
  .whatsapp-button {
    bottom: 80px;
    padding: 10px;
  }

  .button-text {
    display: none;
  }
}
</style>