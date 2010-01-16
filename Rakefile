sources = %w(helpers route request response hash)
sources.map! { |source| "src/pipes/#{source}.php" }
sources.each { |source| file source }
destination = 'pipes.php'
file destination => sources do |task|
    puts 'creating pipes.php:'
    open(task.name, 'w') do |f|
        header = "<?php\n\nnamespace pipes;\n\n"
        header_regex = Regexp.new('^' + Regexp.escape(header))
        f.write header
        task.prerequisites.each do |source|
            puts "- merging #{source}\n"
            f.write open(source).read().gsub(header_regex, '').strip() + "\n\n"
        end
    end
end

require 'rake/clean'
CLEAN.include destination
CLOBBER.include destination

task :default => [:build]

task :build => [:clean, destination]

task :test do
    chdir 'tests' do
        sh 'php run.php'
    end
end
