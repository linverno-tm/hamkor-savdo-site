import { topicById } from "@/data/topics";
import { TopicPage, topicMetadata } from "@/components/sections/TopicPage";

const topic = topicById("tilla");

export const metadata = topicMetadata(topic, "ru");

export default function Page() {
  return <TopicPage topic={topic} lang="ru" />;
}
