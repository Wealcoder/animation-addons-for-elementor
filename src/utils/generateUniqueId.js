export const generateUniqueId = () => {
  const timestamp = Date.now().toString(36); // Convert current time to base-36
  const randomPart = Math.random().toString(36).substring(2, 8); // Random alphanumeric string
  return `${timestamp}-${randomPart}`;
};
