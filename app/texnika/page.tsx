import { topicById } from "@/data/topics";
import { TopicPage, topicMetadata } from "@/components/sections/TopicPage";

const topic = topicById("texnika");

export const metadata = topicMetadata(topic, "uz");

export default function Page() {
  return <TopicPage topic={topic} lang="uz" />;
}
